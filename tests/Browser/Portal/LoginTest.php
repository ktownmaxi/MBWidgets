<?php

namespace FluxErp\Tests\Browser\Portal;

use Laravel\Dusk\Browser;

class LoginTest extends PortalDuskTestCase
{
    public function login(): void
    {
        $this->createLoginUser();
    }

    public function test_login_wrong_credentials()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit(config('flux.portal_domain') . ':8001/')
                ->assertSee('For more transparency, quality and speed in all service processes')
                ->type('email', 'user@usertest.de')
                ->type('password', 'testpassword')
                ->press('Login')
                ->waitForText('Login failed')
                ->assertSee('Login failed');
        });
    }

    public function test_login_successful()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit(config('flux.portal_domain') . ':8001/')
                ->assertSee(__('For more transparency, quality and speed in all service processes'))
                ->type('email', $this->user->login_name)
                ->type('password', $this->password)
                ->press('Login')
                ->waitForReload()
                ->assertRouteIs('portal.dashboard')
                ->assertSee('Return to website');

            $this->openMenu();

            $browser->waitForText($this->user->name)
                ->assertSee($this->user->name);
        });
    }

    public function test_can_see_orders()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');
            $this->openMenu();

            $browser->click('nav [href="/orders"]')
                ->waitForRoute('portal.orders')
                ->assertRouteIs('portal.orders')
                ->waitForText('My orders')
                ->waitForText('Order Number')
                ->waitForText('Order Type -> Name')
                ->waitForText('Commission')
                ->waitForText('Payment State')
                ->waitForText('Total Gross Price')
                ->assertSee('Order Number')
                ->assertSee('Order Type -> Name')
                ->assertSee('Commission')
                ->assertSee('Payment State')
                ->assertSee('Total Gross Price');
        });
    }
}
