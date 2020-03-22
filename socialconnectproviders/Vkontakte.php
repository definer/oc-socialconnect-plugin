<?php

namespace Tohur\SocialConnect\SocialConnectProviders;

use URL;
use Socialite;
use Backend\Widgets\Form;
use Tohur\SocialConnect\Classes\VkontakteProvider;
use Tohur\SocialConnect\SocialConnectProviders\SocialConnectProviderBase;

class Vkontakte extends SocialConnectProviderBase {

    use \October\Rain\Support\Traits\Singleton;

    protected $driver = 'Vkontakte';

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init() {
        parent::init();

        // Socialite uses config files for credentials but we want to pass from
        // our settings page - so override the login method for this provider
        Socialite::extend($this->driver,
            function($app) {
                $providers = \Tohur\SocialConnect\Models\Settings::instance()->get('providers', []);
                $providers['Vkontakte']['redirect'] = URL::route('tohur_socialconnect_provider_callback', ['Vkontakte'], true);
                $provider = Socialite::buildProvider(
                    VkontakteProvider::class, (array) @$providers['Vkontakte']
                );
                return $provider;
            });
    }

    public function isEnabled() {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Vkontakte']['enabled']);
    }

    public function isEnabledForBackend() {
        $providers = $this->settings->get('providers', []);

        return !empty($providers['Vkontakte']['enabledForBackend']);
    }

    public function extendSettingsForm(Form $form) {
        $form->addFields([
            'noop' => [
                'type' => 'partial',
                'path' => '$/tohur/socialconnect/partials/backend/forms/settings/_vkontakte_info.htm',
                'tab' => 'Vkontakte',
            ],
            'providers[Vkontakte][enabled]' => [
                'label' => 'Enabled on frontend?',
                'type' => 'checkbox',
                'comment' => 'Can frontend users log in with Vkontakte?',
                'default' => 'true',
                'span' => 'left',
                'tab' => 'Vkontakte',
            ],
            'providers[Vkontakte][enabledForBackend]' => [
                'label' => 'Enabled on backend?',
                'type' => 'checkbox',
                'comment' => 'Can administrators log into the backend with Vkontakte?',
                'default' => 'false',
                'span' => 'right',
                'tab' => 'Vkontakte',
            ],
            'providers[Vkontakte][client_id]' => [
                'label' => 'Application ID',
                'type' => 'text',
                'tab' => 'Vkontakte',
            ],
            'providers[Vkontakte][client_public]' => [
                'label' => 'Public Key',
                'type' => 'text',
                'tab' => 'Vkontakte',
            ],
            'providers[Vkontakte][client_secret]' => [
                'label' => 'Secure key',
                'type' => 'text',
                'tab' => 'Vkontakte',
            ],
        ], 'primary');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider() {
        return Socialite::with($this->driver)->redirect();
    }

    /**
     * Handles redirecting off to the login provider
     *
     * @return array
     */
    public function handleProviderCallback() {
        $user = Socialite::driver($this->driver)->user();

        return (array) $user;
    }
}
