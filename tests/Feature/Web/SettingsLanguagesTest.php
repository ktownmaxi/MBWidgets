<?php

namespace FluxErp\Tests\Feature\Web;

use FluxErp\Models\Permission;
use FluxErp\Tests\Feature\BaseSetup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsLanguagesTest extends BaseSetup
{
    use DatabaseTransactions;

    public function test_settings_languages_page()
    {
        $this->user->givePermissionTo(Permission::findOrCreate('settings.languages.get', 'web'));

        $this->actingAs($this->user, 'web')->get('/settings/languages')
            ->assertStatus(200);
    }

    public function test_settings_languages_no_user()
    {
        $this->get('/settings/languages')
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_settings_languages_without_permission()
    {
        $this->actingAs($this->user, 'web')->get('/settings/languages')
            ->assertStatus(403);
    }
}
