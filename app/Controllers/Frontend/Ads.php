<?php

namespace App\Controllers\Frontend;

use Throwable;

class Ads extends BaseFrontendController
{
    public function click(int $id = 0)
    {
        $target = '';

        try {
            $banner = db_connect()->table('banners')->where('id', $id)->where('is_active', 1)->get()->getRowArray();
            if ($banner !== null) {
                $target = (string) ($banner['link_url'] ?? '');
                db_connect()->table('banners')->where('id', $id)->set('clicks', 'clicks + 1', false)->update();
            }
        } catch (Throwable $e) {
            log_message('debug', 'Ad click skipped: ' . $e->getMessage());
        }

        return redirect()->to(safe_redirect_url($target, base_url('/')), 302);
    }
}
