<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * bacon/bacon-qr-code is already a dependency (laravel/fortify pulls it in
 * for 2FA's own QR code — see TwoFactorAuthenticatable::twoFactorQrCodeSvg()
 * in vendor, which this mirrors exactly), so the event gallery's QR code
 * reuses it directly rather than adding a package of its own.
 */
class QrCode
{
    public static function svg(string $data, int $size = 240): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle($size, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(31, 41, 55))),
                new SvgImageBackEnd
            )
        ))->writeString($data);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
