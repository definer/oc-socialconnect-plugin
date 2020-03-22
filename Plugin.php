<?php

namespace Tohur\SocialConnect;

use URL;
use App;
use Event;
use Backend\Widgets\Form;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use RainLab\User\Models\UserGroup;
use System\Classes\SettingsManager;
use Illuminate\Foundation\AliasLoader;
use Tohur\SocialConnect\Classes\ProviderManager;
use RainLab\User\Controllers\Users as UsersController;

/**
 * SocialConnect Plugin Information File
 *
 * http://www.mrcasual.com/on/coding/laravel4-package-management-with-composer/
 * https://cartalyst.com/manual/sentry-social
 *
 */
class Plugin extends PluginBase {

    // Make this plugin run on updates page
    public $elevated = true;
    public $require = ['RainLab.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Social Connect',
            'description' => 'Allows visitors to register/sign in with their social media accounts',
            'author' => 'Joshua Webb',
            'icon' => 'icon-users'
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'Social Connect',
                'description' => 'Manage Social Login providers.',
                'category' => SettingsManager::CATEGORY_USERS,
                'icon' => 'icon-users',
                'class' => 'Tohur\SocialConnect\Models\Settings',
                'order' => 600,
                'permissions' => ['rainlab.users.access_settings'],
            ]
        ];
    }

    public function registerComponents()
    {
        return [
            'Tohur\SocialConnect\Components\SocialConnect' => 'socialconnect',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     * @return void
     */
    public function register()
    {
        /*
         * Registers the Social Connect UserExtended module
         */
        if (class_exists('Clake\UserExtended\Classes\UserExtended')) {
            Module::register();
        }
    }

    public function boot()
    {
        // Load socialite
        App::register(\SocialiteProviders\Manager\ServiceProvider::class);
        AliasLoader::getInstance()->alias('Socialite', 'Laravel\Socialite\Facades\Socialite');

        User::extend(function($model) {
            $model->hasMany['tohur_socialconnect_providers'] = ['Tohur\SocialConnect\Models\Provider'];
        });

        User::extend(function($model) {
            $model->addDynamicMethod('addUserGroup', function($group) use ($model) {
                if ($group instanceof \October\Rain\Support\Collection) {
                    return $model->groups()->saveMany($group);
                }

                if (is_string($group)) {
                    $group = UserGroup::whereCode($group)->first();

                    return $model->groups()->save($group);
                }

                if ($group instanceof \RainLab\User\Models\UserGroup) {
                    return $model->groups()->save($group);
                }
            });
        });

        // Add 'Social Logins' column to users list
        UsersController::extendListColumns(function($widget, $model) {
            if (!$model instanceof \RainLab\User\Models\User)
                return;

            $widget->addColumns([
                'tohur_socialconnect_user_providers' => [
                    'label' => 'Social Logins',
                    'type' => 'partial',
                    'path' => '~/plugins/tohur/socialconnect/models/provider/_provider_column.htm',
                    'searchable' => false
                ]
            ]);
        });

        // Generate Social Login settings form
        Event::listen('backend.form.extendFields', function(Form $form) {
            if (!$form->getController() instanceof \System\Controllers\Settings)
                return;
            if (!$form->model instanceof \Tohur\SocialConnect\Models\Settings)
                return;

            foreach (ProviderManager::instance()->listProviders() as $class => $details) {
                $classObj = $class::instance();
                $classObj->extendSettingsForm($form);
            }
        });

        // Add 'Social Providers' field to edit users form
        Event::listen('backend.form.extendFields', function($widget) {
            if (!$widget->getController() instanceof \RainLab\User\Controllers\Users)
                return;
            if (!$widget->model instanceof \RainLab\User\Models\User)
                return;
            if (!in_array($widget->getContext(), ['update', 'preview']))
                return;

            $widget->addFields([
                'tohur_socialconnect_user_providers' => [
                    'label' => 'Social Providers',
                    'type' => 'Tohur\SocialConnect\FormWidgets\LoginProviders',
                ],
            ], 'secondary');
        });

        // Add backend login provider integration
        Event::listen('backend.auth.extendSigninView', function() {
            $providers = ProviderManager::instance()->listProviders();

            $social_connect_links = [];
            foreach ($providers as $provider_class => $provider_details)
                if ($provider_class::instance()->isEnabledForBackend())
                    $social_connect_links[$provider_details['alias']] = URL::route('tohur_socialconnect_provider', [$provider_details['alias']]) . '?s=' . Backend::url() . '&f=' . Backend::url('backend/auth/signin');

            if (!count($social_connect_links))
                return;

            require __DIR__ . '/partials/backend/_login.htm';
        });
    }

    function register_tohur_socialconnect_providers()
    {
        return [
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Facebook' => [
                'label' => 'Facebook',
                'alias' => 'Facebook',
                'description' => 'Log in with Facebook'
            ],
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Twitter' => [
                'label' => 'Twitter',
                'alias' => 'Twitter',
                'description' => 'Log in with Twitter'
            ],
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Google' => [
                'label' => 'Google',
                'alias' => 'Google',
                'description' => 'Log in with Google'
            ],
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Microsoft' => [
                'label' => 'Microsoft',
                'alias' => 'Microsoft',
                'description' => 'Log in with Microsoft'
            ],
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Discord' => [
                'label' => 'Discord',
                'alias' => 'Discord',
                'description' => 'Log in with Discord'
            ],
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Vkontakte' => [
                'label' => 'Vkontakte',
                'alias' => 'Vkontakte',
                'description' => 'Log in with Vkontakte'
            ],
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Twitch' => [
                'label' => 'Twitch',
                'alias' => 'Twitch',
                'description' => 'Log in with Twitch'
            ],
            '\\Tohur\\SocialConnect\\SocialConnectProviders\\Mixer' => [
                'label' => 'Mixer',
                'alias' => 'Mixer',
                'description' => 'Log in with Mixer'
            ]
        ];
    }
}
