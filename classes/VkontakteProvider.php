<?php

namespace Tohur\SocialConnect\Classes;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\VKontakte\Provider;

class VkontakteProvider extends Provider {

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user) {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'screen_name'),
            'name'     => trim(Arr::get($user, 'first_name').' '.Arr::get($user, 'last_name')),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'photo'),
        ]);
    }
}
