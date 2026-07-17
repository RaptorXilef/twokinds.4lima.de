<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

final readonly class SubmitReportRequest
{
    private function __construct(
        public string $comicId,
        public string $reportType,
        public string $description,
        public string $transcriptSuggestion,
        public string $transcriptOriginal,
        public string $submitterName,
        public string $debugInfo,
        public string $ipAddress,
    ) {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        // JSON-Payload auslesen (wurde von der JsonBodyParserMiddleware in $request->input gelegt)
        $input = $request->input;

        if (! empty($input['report_honeypot'])) {
            // Honeypot wurde ausgefüllt -> Bot-Verdacht!
            // Wir werfen eine spezielle Exception, die die Action als "Erfolg" tarnt, um den Bot auszutricksen.
            throw ValidationException::withMessage('HONEYPOT_TRIGGERED');
        }

        $comicId     = \trim((string) ($input['comic_id'] ?? ''));
        $reportType  = \trim((string) ($input['report_type'] ?? ''));
        $description = \trim((string) ($input['report_description'] ?? ''));
        $suggestion  = \trim((string) ($input['report_transcript_suggestion'] ?? ''));

        if ($comicId === '' || $reportType === '') {
            throw ValidationException::withMessage('Fehlende oder ungültige Pflichtfelder.');
        }

        if ($reportType === 'transcript' && $description === '' && $suggestion === '') {
            throw ValidationException::withMessage('Bitte gib eine Beschreibung oder einen Transkript-Vorschlag an.');
        }

        if ($reportType !== 'transcript' && $description === '') {
            throw ValidationException::withMessage('Bitte gib eine Fehlerbeschreibung an.');
        }

        return new self(
            comicId: $comicId,
            reportType: $reportType,
            description: $description,
            transcriptSuggestion: $suggestion,
            transcriptOriginal: \trim((string) ($input['report_transcript_original'] ?? '')),
            submitterName: \trim((string) ($input['report_name'] ?? 'Anonym')),
            debugInfo: \trim((string) ($input['report_debug_info'] ?? '')),
            ipAddress: $request->getIp(),
        );
    }
}
