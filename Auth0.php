<?php namespace Codeception\Module;

use Auth0\SDK\Auth0Api;
use Codeception\TestCase;
use GuzzleHttp\Exception\RequestException;

class Auth0 extends \Codeception\Module
{
    protected $insertedIds = [];

    protected $requiredFields = [
        'domain',
        'token',
    ];

    /**
     * @var Auth0Api
     */
    protected $auth0Api;

    public function _initialize()
    {
        $this->auth0Api = new Auth0Api($this->config['token'], $this->config['domain']);
    }

    public function _after(TestCase $test)
    {
        foreach ($this->insertedIds as $id) {
            try {
                $this->auth0Api->users->delete($id);
            } catch (\Exception $e) {
                $this->debug(sprintf('id: "%s" not removed: "%s"', $id, $e->__toString()));
            }
        }

        $this->insertedIds = [];
        parent::_after($test);
    }

    public function createAuth0User($email, $password, $connection)
    {
        try {
            $info = $this->auth0Api->users->create([
                'email'      => $email,
                'password'   => $password,
                'connection' => $connection,
            ]);

            $this->insertedIds[] = $info['user_id'];
            $this->debugSection('auth0: create user', $info);
            return $info;
        } catch (RequestException $e) {
            $this->failBecauseOfHttpError($e);
        }
    }

    protected function failBecauseOfHttpError(RequestException $e)
    {
        $this->fail(
            sprintf(<<<'EOL'
failed to create user: HTTP ERROR
request:
%s
response:
%s
EOL
                ,
                $e->getRequest()->__toString(),
                $e->hasResponse() ? $e->getResponse()->__toString() : 'null'
            )
        );
    }
}
