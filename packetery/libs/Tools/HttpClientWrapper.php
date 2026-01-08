<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HttpClientWrapper
{
    public const GET_METHOD = 'GET';
    public const POST_METHOD = 'POST';

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function get($url, array $options = []): string
    {
        try {
            $client = HttpClient::create($options);
            $result = $client->request(self::GET_METHOD, $url);
        } catch (TransportExceptionInterface $e) {
            throw new \Exception($e->getMessage());
        }

        return $result->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function post($url, array $options = []): string
    {
        try {
            $client = HttpClient::create($options);
            $result = $client->request(self::POST_METHOD, $url);
        } catch (ExceptionInterface $e) {
            throw new \Exception($e->getMessage());
        }

        return $result->getContent();
    }
}
