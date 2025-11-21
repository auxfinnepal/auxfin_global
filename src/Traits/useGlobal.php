<?php

namespace Auxfin\Global\Traits;

use Auxfin\Global\GlobalService;

trait useGlobal
{
    private GlobalService $globalService;

    public function __construct()
    {
        $this->globalService = new GlobalService();
    }
    public function globalLogin(string $username, string $password)
    {
        return $this->globalService->globalLogin($username, $password);
    }

    public function globalLogout()
    {
        return $this->globalService->globalLogout();
    }

    public function getGlobalBanks(string $country)
    {
        return $this->globalService->getGlobalBanks($country);
    }

    public function registerGlobal(array $request)
    {
        return $this->globalService->registerGlobal($request);
    }
    private function getToken()
    {
        return $this->globalService->getToken();
    }


    public function getAddressById(string $address_id)
    {
        return $this->globalService->getAddressById($address_id);
    }
    public function getUser(array $request)
    {
        return $this->globalService->getUser($request);
    }
    public function getUserById(array $request)
    {
        return $this->globalService->getUserById($request);
    }

    private function getApiToken(string $url, string $client_id, string $client_secret)
    {
        return $this->globalService->getApiToken($url, $client_id, $client_secret);
    }
    public function changePassword(array $request)
    {
        return $this->globalService->changePassword($request);
    }
}
