# FBM-23 - Campaign Draft Planner and Approval Governance

Completed in the packaged source on 2026-06-11.

## Goal

FBM-23 adds a local campaign draft lifecycle before controlled publishing exists. It lets operators prepare a campaign plan, submit it for review, approve or reject it, and freeze an immutable publish snapshot on approval.

## User-facing page

```text
GET /fb-marketing/campaign-drafts
POST /fb-marketing/campaign-drafts
POST /fb-marketing/campaign-drafts/{draft}/submit
POST /fb-marketing/campaign-drafts/{draft}/approve
POST /fb-marketing/campaign-drafts/{draft}/reject
```

The page includes:

```text
Draft status filter
Objective and special-ad-category declaration
Budget type, budget amount, currency and schedule fields
Selected connection, ad account and page
Ready creative asset, selected audience and selected product-set references
Submit, approve and reject lifecycle actions
Approval history
Approval-time immutable publish snapshot
```

## Local tables

```text
fbm_campaign_drafts
fbm_campaign_draft_assets
fbm_campaign_draft_approvals
fbm_campaign_publish_snapshots
```

## Permission

```text
fb_marketing_campaign_drafts_view.read
fb_marketing_campaign_draft_manage.create
fb_marketing_campaign_draft_submit.update
fb_marketing_campaign_draft_approve.update
```

## Security and publishing boundary

This stage does not create campaigns, ad sets, creatives or ads in Meta. It does not mutate budgets, schedules, product sets or audiences. The approval snapshot is a local payload prepared for FBM-24 and contains browser-safe local references only.

Provider sync keys, provider IDs, raw URLs with query strings, request fingerprints, credentials, ciphertext and HMACs are excluded from the campaign draft page.

## Manual Verification Checklist

```text
[ ] Apply all tenant migrations through FBM-23 before opening Campaign Drafts.
[ ] Run php artisan route:list --path=fb-marketing/campaign-drafts in a complete deployment checkout.
[ ] Grant fb_marketing_campaign_drafts_view.read to one test role.
[ ] Confirm the FB MARKETING sidebar shows Campaign Drafts for that role.
[ ] Remove fb_marketing_campaign_drafts_view.read and confirm the page is forbidden and hidden from navigation.
[ ] Grant draft create, submit and approve permissions to separate test operators.
[ ] Create a draft with budget, schedule, special-ad-category declaration and selected creative/audience/product set.
[ ] Submit the draft and confirm it moves to submitted.
[ ] Approve the submitted draft and confirm one fbm_campaign_publish_snapshots row is created.
[ ] Confirm approved drafts cannot be silently resubmitted or edited through this stage.
[ ] Reject a submitted draft and confirm approval history records the action.
[ ] Confirm no Meta API write request, campaign publish, budget mutation or audience mutation occurs.
```

## Notes and limitations

This is a governance and immutable snapshot layer only. Controlled provider publishing remains deferred to FBM-24.
