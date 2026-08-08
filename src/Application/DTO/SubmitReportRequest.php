<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Core\Security\Sanitizer;

final readonly class SubmitReportRequest
{
    private function __construct(
        public ?string $comicId, // ? Optional
        public string $reportType,
        public string $description,
        public string $transcriptSuggestion,
        public string $transcriptOriginal,
        public string $submitterName,
        public bool $wantsCredit,
        public string $debugInfo,
        public string $ipAddress,
    ) {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        // JSON-Payload auslesen (wurde von der JsonBodyParserMiddleware in $request->input gelegt)
        $input = $request->post === [] ? $request->input : $request->post;

        if (isset($input['report_honeypot']) && $input['report_honeypot'] !== '') {
            // Honeypot wurde ausgefüllt -> Bot-Verdacht!
            // Wir werfen eine spezielle Exception, die die Action als "Erfolg" tarnt, um den Bot auszutricksen.
            throw ValidationException::withMessage('HONEYPOT_TRIGGERED');
        }

        $comicIdRaw = $input['comic_id'] ?? '';
        $comicIdStr = \is_string($comicIdRaw) ? \trim($comicIdRaw) : '';
        $comicId    = $comicIdStr !== '' ? $comicIdStr : null;

        $reportType  = Sanitizer::string($input['report_type'] ?? '');
        $description = Sanitizer::string($input['report_description'] ?? '');
        $suggestion  = Sanitizer::html($input['report_transcript_suggestion'] ?? '');

        $wcRaw       = $input['wants_credit'] ?? false;
        $wantsCredit = \in_array($wcRaw, [true, 1, '1', 'true', 'on'], true);

        if ($reportType === '') {
            throw ValidationException::withMessage('Bitte wähle eine Fehler-Kategorie aus.');
        }

        if ($reportType === 'transcript' && $description === '' && $suggestion === '') {
            throw ValidationException::withMessage('Bitte gib eine Beschreibung oder einen Transkript-Vorschlag an.');
        }

        if ($reportType !== 'transcript' && $description === '') {
            throw ValidationException::withMessage('Bitte gib eine Fehlerbeschreibung an.');
        }

        return new self(
            comicId: $comicId !== null ? Sanitizer::string($comicId) : null,
            reportType: $reportType,
            description: $description,
            transcriptSuggestion: $suggestion, // HTML erlaubt!
            transcriptOriginal: Sanitizer::html($input['report_transcript_original'] ?? ''),
            submitterName: Sanitizer::string($input['submitter'] ?? 'Anonym'),
            wantsCredit: $wantsCredit,
            debugInfo: Sanitizer::string($input['report_debug_info'] ?? ''),
            ipAddress: $request->getIp(),
        );
    }
}
