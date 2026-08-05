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

        if (! empty($input['report_honeypot'])) {
            // Honeypot wurde ausgefüllt -> Bot-Verdacht!
            // Wir werfen eine spezielle Exception, die die Action als "Erfolg" tarnt, um den Bot auszutricksen.
            throw ValidationException::withMessage('HONEYPOT_TRIGGERED');
        }

        $comicId     = \trim((string) ($input['comic_id'] ?? '')) ?: null; // Leer zu null
        $reportType  = \trim((string) ($input['report_type'] ?? ''));
        $description = \trim((string) ($input['report_description'] ?? ''));
        $suggestion  = \trim((string) ($input['report_transcript_suggestion'] ?? ''));
        $wantsCredit = ! empty($input['wants_credit']); // NEU: Checkbox als boolean

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
            comicId: Sanitizer::string($comicId),
            reportType: Sanitizer::string($reportType),
            description: Sanitizer::string($description),
            transcriptSuggestion: Sanitizer::html($suggestion), // HTML erlaubt!
            transcriptOriginal: Sanitizer::html($input['report_transcript_original'] ?? ''),
            submitterName: Sanitizer::string($input['submitter'] ?? 'Anonym'),
            wantsCredit: $wantsCredit,
            debugInfo: Sanitizer::string($input['report_debug_info'] ?? ''),
            ipAddress: $request->getIp(),
        );
    }
}
