<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\System\RemoteResourceProberInterface;
use CurlHandle;

final readonly class CurlRemoteResourceProber implements RemoteResourceProberInterface
{
    public function probeExtension(string $url, string $fallback = 'png'): string
    {
        if (!\function_exists('curl_multi_init')) {
            return $fallback;
        }

        $extensions = ['png', 'jpg', 'gif', 'jpeg', 'webp'];
        $multiHandle = \curl_multi_init();
        $curlHandles = [];

        foreach ($extensions as $ext) {
            $ch = \curl_init($url . '.' . $ext);
            if ($ch === false) {
                continue;
            }

            \curl_setopt_array($ch, [
                \CURLOPT_NOBODY => true,
                \CURLOPT_TIMEOUT => 2,
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_FOLLOWLOCATION => true,
                \CURLOPT_SSL_VERIFYPEER => false,
                \CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) TwokindsAdminProbe/1.0',
            ]);
            \curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[$ext] = $ch;
        }

        $active = 0;
        $foundExt = null;

        do {
            $status = \curl_multi_exec($multiHandle, $active);
            while (($info = \curl_multi_info_read($multiHandle)) !== false) {
                $ch = $info['handle'] ?? null;
                if (!$ch instanceof CurlHandle) {
                    continue;
                }

                $codeRaw = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
                $code = \is_int($codeRaw) ? $codeRaw : 0;

                $contentTypeRaw = \curl_getinfo($ch, \CURLINFO_CONTENT_TYPE);
                $contentType = \is_string($contentTypeRaw) ? $contentTypeRaw : '';

                if ($code === 200 && \str_starts_with($contentType, 'image/')) {
                    $effUrlRaw = \curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL);
                    $effUrl = \is_string($effUrlRaw) ? $effUrlRaw : '';
                    $foundExt = \strtolower(\pathinfo($effUrl, \PATHINFO_EXTENSION));

                    break 2;
                }
            }
            if (!$active) {
                continue;
            }

            \curl_multi_select($multiHandle, 0.05);
        } while ($active > 0 && $status === \CURLM_OK);

        foreach ($curlHandles as $ch) {
            \curl_multi_remove_handle($multiHandle, $ch);
        }
        \curl_multi_close($multiHandle);

        return $foundExt !== null && $foundExt !== '' ? $foundExt : $fallback;
    }
}
