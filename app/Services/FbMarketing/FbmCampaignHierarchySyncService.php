<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmCreative;
use App\Models\FbMarketing\FbmSyncRun;
use App\Models\FbMarketing\FbmSyncRunItem;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FbmCampaignHierarchySyncService
{
    public const FAMILIES = ['campaigns', 'ad_sets', 'ads', 'creatives'];

    public function __construct(protected FbmGraphClient $graphClient, protected FbmGraphApiVersionPolicy $versionPolicy) {}

    public static function schemaReady(): bool
    {
        foreach (['fbm_campaigns','fbm_ad_sets','fbm_ads','fbm_creatives','fbm_sync_run_items'] as $table) {
            if (!Schema::hasTable($table)) { return false; }
        }
        return true;
    }

    public function sync(FbmConnection $connection, ?FbmSyncRun $run = null, ?int $maxSelectedAdAccounts = null): array
    {
        if (!self::schemaReady()) {
            return $this->summary('failed', ['Campaign hierarchy schema is not ready.'], [], []);
        }

        $version = $this->versionPolicy->resolve($connection->graph_api_version);
        $accounts = FbmAdAccount::query()
            ->where('fbm_connection_id', (int) $connection->id)
            ->where('is_available', true)
            ->where('is_selected', true)
            ->when(
                $maxSelectedAdAccounts !== null,
                fn($query) => $query->orderBy('id'),
                fn($query) => $query->orderBy('asset_name')->orderBy('id')
            )
            ->limit(max(1, min(100, $maxSelectedAdAccounts ?: (int) config('fb_marketing.campaign_hierarchy.max_selected_ad_accounts', 25))))
            ->get();

        if ($accounts->isEmpty()) {
            return $this->summary('skipped', ['Select at least one available Ad Account before campaign hierarchy sync.'], [], []);
        }

        $counts = array_fill_keys(self::FAMILIES, 0);
        $warnings = [];
        $complete = array_fill_keys(self::FAMILIES, true);

        foreach ($accounts as $account) {
            foreach (self::FAMILIES as $family) {
                $result = $this->syncFamily($connection, $account, $version, $family);
                $counts[$family] += (int) $result['seen_count'];
                if ($result['status'] !== 'success') {
                    $complete[$family] = false;
                    $warnings[] = $result['redacted_message'] ?: ('Meta ' . $family . ' edge did not complete safely.');
                }
                FbmSyncRunItem::query()->create(array_merge($result, [
                    'fbm_sync_run_id' => $run?->id,
                    'fbm_connection_id' => (int) $connection->id,
                    'fbm_ad_account_id' => (int) $account->id,
                ]));
            }
        }

        $status = $warnings === [] ? 'success' : ($counts['campaigns'] + $counts['ad_sets'] + $counts['ads'] + $counts['creatives'] > 0 ? 'partial_success' : 'failed');
        return $this->summary($status, array_values(array_unique(array_slice($warnings, 0, 20))), $counts, $complete);
    }

    protected function syncFamily(FbmConnection $connection, FbmAdAccount $account, string $version, string $family): array
    {
        $seen = [];
        $upserted = 0;
        $message = null;
        try {
            [$path, $fields, $operationKey] = $this->edge($account, $family);
            $result = $this->graphClient->paginateEdge($connection, $path, $fields, $version, $operationKey, 'campaign_hierarchy');
            $rows = is_array($result['items'] ?? null) ? $result['items'] : [];

            foreach ($rows as $row) {
                if (!is_array($row)) { continue; }
                $providerId = $this->providerId($row['id'] ?? null);
                if ($providerId === null) { continue; }
                $seen[] = $providerId;
                $upserted += $this->upsert($connection, $account, $family, $providerId, $row) ? 1 : 0;
            }

            $unavailable = 0;
            if (!empty($result['complete'])) {
                $unavailable = $this->markUnavailable($connection, $account, $family, $seen);
            } else {
                $message = $result['redacted_message'] ?: 'Meta hierarchy edge was incomplete. Existing unseen rows were preserved.';
            }

            return [
                'family' => $family,
                'status' => !empty($result['complete']) ? 'success' : 'partial_success',
                'seen_count' => count($seen),
                'upserted_count' => $upserted,
                'unavailable_count' => $unavailable,
                'warning_count' => !empty($result['complete']) ? 0 : 1,
                'redacted_message' => $this->safe($message, 500),
                'safe_summary' => ['complete' => !empty($result['complete']), 'truncated' => !empty($result['truncated']), 'pages' => (int) ($result['pages'] ?? 0)],
            ];
        } catch (Throwable $exception) {
            return [
                'family' => $family,
                'status' => 'failed',
                'seen_count' => count($seen),
                'upserted_count' => $upserted,
                'unavailable_count' => 0,
                'warning_count' => 1,
                'redacted_message' => 'Campaign hierarchy sync stopped safely for this family. Existing unseen rows were preserved.',
                'safe_summary' => ['exception_class' => class_basename($exception)],
            ];
        }
    }

    protected function edge(FbmAdAccount $account, string $family): array
    {
        $adAccountNode = $account->provider_asset_id;
        if ($family === 'campaigns') { return [$adAccountNode . '/campaigns', ['id','name','objective','status','effective_status','created_time','updated_time'], 'campaign_hierarchy_campaigns']; }
        if ($family === 'ad_sets') { return [$adAccountNode . '/adsets', ['id','name','campaign_id','status','effective_status','optimization_goal','billing_event','daily_budget','lifetime_budget','created_time','updated_time'], 'campaign_hierarchy_ad_sets']; }
        if ($family === 'ads') { return [$adAccountNode . '/ads', ['id','name','campaign_id','adset_id','creative{id}','status','effective_status','created_time','updated_time'], 'campaign_hierarchy_ads']; }
        return [$adAccountNode . '/adcreatives', ['id','name','title','body','object_type','thumbnail_url'], 'campaign_hierarchy_creatives'];
    }

    protected function upsert(FbmConnection $connection, FbmAdAccount $account, string $family, string $providerId, array $row): bool
    {
        $base = ['fbm_connection_id'=>(int)$connection->id,'fbm_ad_account_id'=>(int)$account->id,'provider_sync_key'=>$providerId,'name'=>$this->safe($row['name'] ?? null,255),'is_available'=>true,'last_seen_at'=>now()];
        if ($family === 'campaigns') {
            FbmCampaign::query()->updateOrCreate(['fbm_connection_id'=>(int)$connection->id,'provider_sync_key'=>$providerId], $base + ['objective'=>$this->safe($row['objective']??null,120),'configured_status'=>$this->safe($row['status']??null,80),'effective_status'=>$this->safe($row['effective_status']??null,80),'provider_created_time'=>$this->time($row['created_time']??null),'provider_updated_time'=>$this->time($row['updated_time']??null)]); return true;
        }
        if ($family === 'ad_sets') {
            FbmAdSet::query()->updateOrCreate(['fbm_connection_id'=>(int)$connection->id,'provider_sync_key'=>$providerId], $base + ['fbm_campaign_id'=>$this->campaignId($connection,$row['campaign_id']??null),'configured_status'=>$this->safe($row['status']??null,80),'effective_status'=>$this->safe($row['effective_status']??null,80),'optimization_goal'=>$this->safe($row['optimization_goal']??null,120),'billing_event'=>$this->safe($row['billing_event']??null,120),'daily_budget'=>$this->safe($row['daily_budget']??null,80),'lifetime_budget'=>$this->safe($row['lifetime_budget']??null,80),'provider_created_time'=>$this->time($row['created_time']??null),'provider_updated_time'=>$this->time($row['updated_time']??null)]); return true;
        }
        if ($family === 'ads') {
            $creativeId = is_array($row['creative'] ?? null) ? $this->providerId($row['creative']['id'] ?? null) : null;
            FbmAd::query()->updateOrCreate(['fbm_connection_id'=>(int)$connection->id,'provider_sync_key'=>$providerId], $base + ['fbm_campaign_id'=>$this->campaignId($connection,$row['campaign_id']??null),'fbm_ad_set_id'=>$this->adSetId($connection,$row['adset_id']??null),'provider_creative_sync_key'=>$creativeId,'fbm_creative_id'=>$creativeId?$this->creativeId($connection,$creativeId):null,'configured_status'=>$this->safe($row['status']??null,80),'effective_status'=>$this->safe($row['effective_status']??null,80),'provider_created_time'=>$this->time($row['created_time']??null),'provider_updated_time'=>$this->time($row['updated_time']??null)]); return true;
        }
        FbmCreative::query()->updateOrCreate(['fbm_connection_id'=>(int)$connection->id,'provider_sync_key'=>$providerId], $base + ['title'=>$this->safe($row['title']??null,255),'body'=>$this->safe($row['body']??null,500),'object_type'=>$this->safe($row['object_type']??null,80),'thumbnail_url_hash'=>$this->hash($row['thumbnail_url']??null)]);
        FbmAd::query()->where('fbm_connection_id',(int)$connection->id)->where('provider_creative_sync_key',$providerId)->update(['fbm_creative_id'=>FbmCreative::query()->where('fbm_connection_id',(int)$connection->id)->where('provider_sync_key',$providerId)->value('id')]);
        return true;
    }

    protected function markUnavailable(FbmConnection $connection, FbmAdAccount $account, string $family, array $seen): int
    {
        $class = ['campaigns'=>FbmCampaign::class,'ad_sets'=>FbmAdSet::class,'ads'=>FbmAd::class,'creatives'=>FbmCreative::class][$family];
        $q = $class::query()->where('fbm_connection_id',(int)$connection->id)->where('fbm_ad_account_id',(int)$account->id)->where('is_available',true);
        if ($seen !== []) { $q->whereNotIn('provider_sync_key', $seen); }
        $count = (clone $q)->count();
        $q->update(['is_available'=>false]);
        return (int) $count;
    }

    protected function campaignId(FbmConnection $c, $providerId): ?int { $id=$this->providerId($providerId); return $id?FbmCampaign::query()->where('fbm_connection_id',(int)$c->id)->where('provider_sync_key',$id)->value('id'):null; }
    protected function adSetId(FbmConnection $c, $providerId): ?int { $id=$this->providerId($providerId); return $id?FbmAdSet::query()->where('fbm_connection_id',(int)$c->id)->where('provider_sync_key',$id)->value('id'):null; }
    protected function creativeId(FbmConnection $c, $providerId): ?int { return FbmCreative::query()->where('fbm_connection_id',(int)$c->id)->where('provider_sync_key',$providerId)->value('id'); }
    protected function providerId($v): ?string { if(!is_scalar($v)){return null;} $v=trim((string)$v); return $v!=='' && strlen($v)<=190 && preg_match('/^[A-Za-z0-9_.:-]+$/',$v)?$v:null; }
    protected function safe($v,int $l): ?string { if(!is_scalar($v)){return null;} $v=trim(SecretRedactor::redactString((string)$v)); return $v===''?null:mb_substr($v,0,$l); }
    protected function time($v) { if (!is_scalar($v) || trim((string)$v) === '') { return null; } $timestamp = strtotime((string) $v); return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null; }
    protected function hash($v): ?string { return is_scalar($v) && trim((string)$v)!=='' ? hash_hmac('sha256',(string)$v,(string)config('app.key','')) : null; }
    protected function summary(string $status, array $warnings, array $counts, array $complete): array { return ['status'=>$status,'warning_count'=>count($warnings),'warnings'=>$warnings,'counts'=>$counts,'complete'=>$complete,'message'=>$status==='success'?'Read-only campaign hierarchy sync completed successfully.':($status==='skipped'?'Campaign hierarchy sync skipped safely.':'Read-only campaign hierarchy sync completed with safe warnings.')]; }
}
