<?php

namespace App\View\Composers;

use App\Support\SiteSettings;
use Illuminate\View\View;

/**
 * Puts the editable site settings in front of the layout.
 *
 * Without this the settings screen would save rows that no page ever reads —
 * a form that says "enregistré" and changes nothing, which is worse than no
 * form at all because the failure is invisible.
 */
class SiteComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'site' => SiteSettings::all(),
            'siteSocial' => SiteSettings::socialLinks(),
        ]);
    }
}
