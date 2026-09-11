<?php

namespace App\Services\Crm\CampaignDispatch;

use App\Data\Crm\CampaignDispatch\CrmCampaignEmailDispatchCommand;
use App\Services\Crm\CrmCampaignRecipientSnapshotService;
use Illuminate\Validation\ValidationException;

/**
 * Pure, non-sending CRM Email SMTP policy boundary. Stage 30 uses this service
 * for local protocol hardening metadata only. It must not connect to SMTP,
 * invoke a mailer, queue work, schedule delivery, or mutate dispatch ledgers.
 */
class EmailCampaignSmtpProtocol
{
    public const MAX_RECIPIENTS = 10;

    public const MAX_SUBJECT_LENGTH = 255;

    public const MAX_MESSAGE_BODY_LENGTH = 20000;

    public const MAX_SENDER_NAME_LENGTH = 160;

    public const RESULT_CATEGORIES = [
        'succeeded',
        'rejected',
        'transport_failure',
        'timeout_unknown',
        'internal_unknown',
    ];

    public function __construct(protected CrmCampaignRecipientSnapshotService $recipientSnapshotService)
    {
    }

    public function readiness(): array
    {
        return [
            'ready' => true,
            'checks' => [
                [
                    'key' => 'email_single_recipient_boundary',
                    'label' => 'Future single-recipient Email transport boundary',
                    'passed' => true,
                    'message' => 'A future CRM Email transport must process exactly one immutable normalized recipient command per transport call.',
                ],
                [
                    'key' => 'email_recipient_cap',
                    'label' => 'Future bounded Email recipient cap',
                    'passed' => true,
                    'message' => 'A future manually confirmed CRM Email attempt is capped server-side at ' . self::MAX_RECIPIENTS . ' recipients. No automatic truncation is allowed.',
                ],
                [
                    'key' => 'email_content_bounds',
                    'label' => 'Future Email subject and message bounds',
                    'passed' => true,
                    'message' => 'A future Email command requires a subject of at most ' . self::MAX_SUBJECT_LENGTH . ' characters and a message body of at most ' . number_format(self::MAX_MESSAGE_BODY_LENGTH) . ' characters.',
                ],
                [
                    'key' => 'email_header_injection_guard',
                    'label' => 'Email header-injection guard',
                    'passed' => true,
                    'message' => 'Future sender metadata and Email subjects reject carriage-return or line-feed characters before any transport is allowed.',
                ],
                [
                    'key' => 'email_encryption_mapping',
                    'label' => 'Recognized SMTP encryption mapping',
                    'passed' => true,
                    'message' => 'Future SMTP execution accepts only these modes: none, TLS, or SSL.',
                ],
                [
                    'key' => 'email_unknown_outcome_policy',
                    'label' => 'Unknown-outcome and resend policy',
                    'passed' => true,
                    'message' => 'A future Email adapter must not automatically retry uncertain outcomes and must not expose browser resend after an interrupted execution.',
                ],
                [
                    'key' => 'email_safe_result_categories',
                    'label' => 'Provider-neutral Email result categories',
                    'passed' => true,
                    'message' => 'A future adapter is constrained to safe provider-neutral result categories without returning raw SMTP exception text.',
                ],
            ],
            'future_recipient_cap' => self::MAX_RECIPIENTS,
            'future_max_subject_length' => self::MAX_SUBJECT_LENGTH,
            'future_max_message_body_length' => self::MAX_MESSAGE_BODY_LENGTH,
            'future_max_sender_name_length' => self::MAX_SENDER_NAME_LENGTH,
            'future_encryption_modes' => ['none', 'tls', 'ssl'],
            'future_result_categories' => self::RESULT_CATEGORIES,
            'single_recipient_requests_only' => true,
            'automatic_retry_available' => false,
            'browser_resend_available' => false,
            'transport_available' => false,
        ];
    }

    /**
     * Validate a future server-only single-recipient command and return the
     * canonical normalized recipient in memory. The returned value is
     * sensitive and must never be logged, persisted, or exposed to a browser.
     */
    public function validateFutureCommand(CrmCampaignEmailDispatchCommand $command): string
    {
        $destination = $this->recipientSnapshotService->normalizeEmailDestination($command->destination());
        if ($destination === null) {
            $this->throwValidation('destination', 'The Email destination could not be normalized safely.');
        }

        $subject = trim($command->subject());
        if ($subject === '') {
            $this->throwValidation('subject', 'An Email subject is required.');
        }
        if ($this->containsHeaderInjection($subject)) {
            $this->throwValidation('subject', 'The Email subject contains forbidden header line-break characters.');
        }
        if ($this->stringLength($subject) > self::MAX_SUBJECT_LENGTH) {
            $this->throwValidation('subject', 'The Email subject exceeds the server-side character limit.');
        }

        $messageBody = trim($command->messageBody());
        if ($messageBody === '') {
            $this->throwValidation('message_body', 'An Email message body is required.');
        }
        if ($this->stringLength($messageBody) > self::MAX_MESSAGE_BODY_LENGTH) {
            $this->throwValidation('message_body', 'The Email message body exceeds the server-side character limit.');
        }

        $this->validateSenderMetadata($command->smtp()->fromName(), $command->smtp()->fromEmail());

        return $destination;
    }

    public function validateSenderMetadata(string $fromName, string $fromEmail): void
    {
        $fromName = trim($fromName);
        $fromEmail = trim($fromEmail);

        if ($fromName === '') {
            $this->throwValidation('sender', 'A server-side Email sender name is required.');
        }
        if ($this->containsHeaderInjection($fromName) || $this->containsHeaderInjection($fromEmail)) {
            $this->throwValidation('sender', 'Email sender metadata contains forbidden header line-break characters.');
        }
        if ($this->stringLength($fromName) > self::MAX_SENDER_NAME_LENGTH) {
            $this->throwValidation('sender', 'The Email sender name exceeds the server-side character limit.');
        }
        if ($this->recipientSnapshotService->normalizeEmailDestination($fromEmail) === null) {
            $this->throwValidation('sender', 'A valid server-side Email sender address is required.');
        }
    }

    public function mapEncryptionMode($value): ?string
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false || !in_array($value, [0, 1, 2], true)) {
            $this->throwValidation('smtp_encryption', 'The configured SMTP encryption mode must be none, TLS, or SSL.');
        }

        return match ($value) {
            0 => null,
            1 => 'tls',
            2 => 'ssl',
        };
    }

    public function safeFailureSummary(string $category): string
    {
        return match ($category) {
            'rejected' => 'The SMTP transport rejected the Email command.',
            'transport_failure' => 'The SMTP transport could not complete the Email command.',
            'timeout_unknown' => 'The SMTP outcome is unknown because the transport timed out. Do not retry automatically.',
            default => 'The SMTP outcome is unknown. Do not retry automatically.',
        };
    }

    protected function containsHeaderInjection(string $value): bool
    {
        return preg_match('/[\r\n]/', $value) === 1;
    }

    protected function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    protected function throwValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
