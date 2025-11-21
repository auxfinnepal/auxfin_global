<?php

namespace Auxfin\Global;

use App\Models\SsoToken;
use Carbon\Carbon;
use GuzzleHttp\Client;

class GlobalService
{
    private Client $client;
    private string $apiUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->setGlobalConfig();
    }

    public function globalLogin(string $username, string $password)
    {
        $graphQLBody = [
            "query" => 'mutation login($username: String!, $password: String!) {
                login(
                    input:{
                        username: $username,
                        password: $password
                    }
                ) {
                    access_token
                    expires_in
                    refresh_token
                    token_type
                    user {
                        sup_id
                        sup_name
                        passwordenc
                        roles{
                          id
                          name
                        }
                        user_info {
                          userid
                            first_name
                            last_name
                            birthday
                            address_country
                            address_state
                            address_zone
                            address_zone1
                            address_city
                            address_locality
                            user_group
                            address_id
                            phone
                            mobile
                            website
                            umva_org
                            umva_cafe
                            umva_langs
                            umva_profession
                            umva_cardid
                            umva_idcard_jpg
                            umva_photo_jpg
                            umva_signature_jpg
                            gender
                            default_account_id
                            latitude
                            longitude
                            head_of_family
                            family_no
                            addresses {
                                address_id
                                first_path
                                detail {
                                	area1
                                	area2
                                	area3
                                	area4
                                	area5
                                	country
                                	country_code
                                    group
                                }
					        }
                        }

                        default_account{
                            bank_account{
                              bank{
                                bank_id
                                bank_code
                              }
                            }
                        }
                        accounts {
                            account_id
                            value_type_id

                            bank_account {
                                bank2account_id
                                bank_id
                                account_id
                                bankaccount
                                stamp
                                value_type_id
                                bank {
                                    bank_id
                                    bank_code
                                }
                            }
                            user_account_status {
					            user_status_type {
                                    id
						            status
					            }
				            }
                            value_type {
                                id
                                code
                            }
                        }
                    }
                }
            }',
            "variables" => [
                "username" => $username,
                "password" => $password
            ],
        ];

        $response = $this->client->post($this->apiUrl . "/graphql", [
            "headers" => [
                "Content-Type" => "application/json",
            ],
            "body" => json_encode($graphQLBody),
        ]);

        $data = json_decode($response->getBody()->getContents());
        if (isset($data->errors)) {
            $error = $data->errors;
            $get_message = isset($error[0]->extensions)
                ? $error[0]->extensions->debugMessage
                : $error[0]->message[0];
            throw new \Exception($get_message, 422);
        }

        return $data->data->login;
    }

    public function globalLogout()
    {
        $graphQLBody = [
            "query" => 'mutation logout {
                logout {
                    success
                }
            }'
        ];

        $response = $this->client->post($this->apiUrl . "/graphql", [
            "headers" => [
                "Content-Type" => "application/json",
            ],
            "body" => json_encode($graphQLBody),
        ]);

        $data = json_decode($response->getBody()->getContents());
        return $data;
    }

    public function getGlobalBanks(string $country)
    {
        $graphQLBody = [
            "query" => 'query getBanks($country: String!,$status:Boolean) {
                getBanks(country:$country,status:$status) {
                bank_id
                bank_code
                bank_name
                value_types{
                code
                }
                }
            }',
            "variables" => [
                "country" => $country,
                "status" => true
            ],
        ];

        $response = $this->client->post($this->apiUrl . "/graphql", [
            "headers" => [
                "Content-Type" => "application/json",
            ],
            "body" => json_encode($graphQLBody),
        ]);

        $data = json_decode($response->getBody()->getContents());
        return $data->data->getBanks;
    }

    public function registerGlobal(array $request)
    {
        $token = SsoToken::first()->access_token ?? null;

        if (!$token) {
            $token = $this->getToken();
        }

        $graphQLBody = [
            "query" => 'mutation massRegister($input: MassUserUpload) {
                massRegister(input:$input) {
                sup_id
    sup_name
    sup_status
    user_password
    user_email
    user_level
    passwordenc
    umva_org_id
    user_level_id
    secret_question
    secret_answer
    gender
    otp
    user_info {
      userid
      first_name
      last_name
      gender
      default_account_id
      head_of_family
      longitude
      latitude
      mobile
      birthday
      umva_cardid
      umva_photo_jpg
      umva_idcard_jpg
      umva_photo_jpg_pending
      umva_signature_jpg
      umva_idcard_jpg_pending
      address_street1
      address_street2
      address_country
      address_state
      address_zone
      address_zone1
      address_city
      address_locality
      address_postalcode
      user_group
      address_id
      phone
      personal_info
      website
      picture_jpg640
      picture_jpg60
      umva_org
      umva_cafe
      umva_langs
      umva_skype
      umva_profession
      creditworthiness
      family_no
      accessible_groups
      role_names
      marital_status_id
    }
farmer {
      id
      user_id
      farmer_nr
      kbs_reg_nr
      contract_to_factory
      contract_nr
      association_name
    }
    farmer_fields {
      id
      user_id
      farmer_field
      field_size
      owner
      land
      boundaries
      field_type
      unit_id
      status
      created_at
      updated_at
    }
                }
                }',
            "operationName" => "massRegister",
            "variables" => ["input" => ["data" => $request, "type" => ""]]
        ];

        $response = $this->client->request('POST', $this->apiUrl . '/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($graphQLBody)
        ]);

        $bodyData = json_decode($response->getBody()->getContents());

        if (property_exists($bodyData, "errors")) {
            throw new \Exception(json_encode($bodyData->errors));
        }

        return $bodyData->data->massRegister;
    }

    public function getAddressById(string $address_id)
    {
        $token = SsoToken::first()->access_token ?? null;

        if (!$token) {
            $token = $this->getToken();
        }

        $graphqlQuery = 'query getAddressById($id:ID!){
            getAddressById(id:$id){
                id
                country_code,
                country
                area1
                area2
                area3
                area4
                area5
                group
                latitude
                longitude
            }
       }';

        $graphQLBody = [
            "query" => $graphqlQuery,
            "variables" => ["id" => $address_id],
            "operationName" => "getAddressById"
        ];

        $response = $this->client->request('POST', $this->apiUrl . '/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($graphQLBody)
        ]);

        $bodyData = json_decode($response->getBody()->getContents());
        return $bodyData;
    }

    public function getUser(array $request)
    {
        $token = SsoToken::first()->access_token ?? null;

        if (!$token) {
            $token = $this->getToken();
        }

        $graphQLBody = [
            "query" => 'query findUserByUmvaId (
  $findByUmvaId: String
  $findByMobileNumber: ID
  $joinInfo: Boolean
) {
  findUserByUmvaId(
    findByUmvaId: $findByUmvaId
    findByMobileNumber: $findByMobileNumber
    joinInfo: $joinInfo
  ) {
    sup_id
    sup_name
    sup_status
    user_password
    user_email
    user_level
    passwordenc
    secret_question
    secret_answer
    gender
    otp
    user_info {
      userid
      first_name
      last_name
      gender
      default_account_id
      head_of_family
      longitude
      latitude
      mobile
      birthday
      addresses{
        address_id
        address_type
      }
    }
}
}
',
            "operationName" => "findUserByUmvaId",
            "variables" => $request
        ];

        $response = $this->client->request('POST', $this->apiUrl . '/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($graphQLBody)
        ]);

        $bodyData = json_decode($response->getBody()->getContents());
        return $bodyData;
    }

    public function getUserById(array $request)
    {
        $token = $request['global_token'];

        unset($request['global_token']);

        if (!$token) {
            $token = $this->getToken();
        }

        $graphQLBody = [
            "query" => 'query findUserById (
  $findById: ID
) {
  findUserById(
    findById: $findById
  ) {
    sup_id
    sup_name
    sup_status
    user_password
    user_email
    user_level
    passwordenc
    secret_question
    secret_answer
    gender
    otp
    user_info {
      userid
      first_name
      last_name
      gender
      default_account_id
      head_of_family
      longitude
      latitude
      mobile
      birthday
      addresses{
        address_id
        address_type
      }
    }
}
}
',
            "operationName" => "findUserById",
            "variables" => $request
        ];

        $response = $this->client->request('POST', $this->apiUrl . '/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($graphQLBody)
        ]);

        $bodyData = json_decode($response->getBody()->getContents());
        return $bodyData;
    }

    private function getToken()
    {
        $client_id = env("GLOBAL_CLIENT_ID");
        $client_secret = env("GLOBAL_CLIENT_SECRET");
        $url = $this->apiUrl;
        $ssoToken = SsoToken::where('product_name', 'Global')->first();

        if ($ssoToken) {
            $expires_in = Carbon::parse($ssoToken->expires_in);
            if ($expires_in->isPast()) {
                $response = $this->getApiToken($url, $client_id, $client_secret);

                $newToken = json_decode((string)$response->getBody(), true);
                $token = $newToken['access_token'];
                $expires_in = $newToken['expires_in'];
                $ssoToken->update(['access_token' => $token, 'expires_in' => Carbon::now()->addSeconds($expires_in)]);
            }
        } else {
            $response = $this->getApiToken($url, $client_id, $client_secret);
            $newToken = json_decode((string)$response->getBody(), true);
            $token = $newToken['access_token'];
            $expires_in = $newToken['expires_in'];
            $ssoToken = SsoToken::create(['access_token' => $token, 'expires_in' => Carbon::now()->addSeconds($expires_in), 'product_name' => 'Global', 'refresh_token' => '']);
        }

        return $ssoToken->access_token;
    }

    private function getApiToken(string $url, string $client_id, string $client_secret)
    {
        if (env('GLOBAL_AUTH') === 'password') {
            $response = $this->client->post($url . "/oauth/token", [
                "form_params" => [
                    "grant_type" => "password",
                    "client_id" => $client_id,
                    "client_secret" => $client_secret,
                    "username" => env('GLOBAL_SUPER_USER'),
                    "password" => env('GLOBAL_SUPERUSER_PASSWORD'),
                    "scope" => "*",
                ],
            ]);
        } else {
            $response = $this->client->post($url . "/oauth/token", [
                "form_params" => [
                    "grant_type" => "client_credentials",
                    "client_id" => $client_id,
                    "client_secret" => $client_secret,
                    "scope" => "*",
                ],
            ]);
        }

        return $response;
    }

    private function setGlobalConfig()
    {
        config(['global.api' => env('GLOBAL_API_URL')]);
        $this->apiUrl = config('global.api');
    }

    public function changePassword($request)
    {
        $token = SsoToken::where('product_name', "Global")->first()->access_token ?? null;

        if (!$token) {
            $token = $this->getToken();
        }


        $graphQLBody = [
            "query" => 'mutation changePassword($input: passwordChangeInputs) {
                changePassword(input:$input)
            }',
            "operationName" => "changePassword",
            "variables" => ["input"=>$request]
        ];


        $response = $this
            ->client->request('POST', $this->apiUrl . '/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($graphQLBody)
        ]);

        $bodyData = json_decode($response->getBody()->getContents());
        if (property_exists($bodyData, 'errors')) {

            if (is_array($bodyData->errors)) {

                $message = $bodyData->errors[0]->message;

                if (is_array($message)) {
                    $message = $message[0];
                }

                throw new \Exception($message);
            } else {

                $message = $bodyData->errors->message;

                throw new \Exception($message);
            }
        }

        return "password_changed_successfully";
    }

}
