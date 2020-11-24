<?php

namespace MessageBird\Resources\Conversation;

use MessageBird\Common;
use MessageBird\Common\HttpClient;
use MessageBird\Common\ResponseError;
use MessageBird\Exceptions\AuthenticateException;
use MessageBird\Exceptions\HttpException;
use MessageBird\Exceptions\RequestException;
use MessageBird\Exceptions\ServerException;
use MessageBird\Objects\BaseList;
use MessageBird\Objects\Conversation\ContactIdentifier;

/**
 * ContactIdentifiers does not extend Base because PHP won't let us add parameters to the
 * create and getList functions in overrides.
 */
class ContactIdentifiers
{
    const HTTP_STATUS_OK = 200;

    const RESOURCE_NAME = 'contacts/%s/identifiers';

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var ContactIdentifier
     */
    protected $object;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;

        $this->setObject(new ContactIdentifier());
    }

    /**
     * @return ContactIdentifier
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param ContactIdentifier $object
     */
    public function setObject($object)
    {
        $this->object = $object;
    }

    /**
     * Add an identifier to contact.
     *
     * @param string $contactId
     * @param ContactIdentifier $object
     * @param string[]|null $query
     *
     * @return ContactIdentifiers
     *
     * @throws HttpException
     * @throws RequestException
     * @throws ServerException|AuthenticateException
     */
    public function create($contactId, $object, $query = null)
    {
        $body = json_encode($object);

        list(, , $body) = $this->httpClient->performHttpRequest(
            HttpClient::REQUEST_POST,
            $this->getResourceNameWithId($contactId),
            $query,
            $body
        );

        return $this->processRequest($body);
    }

    /**
     * @param $contactId
     * @param $identifierId
     *
     * @return bool
     *
     * @throws RequestException
     * @throws ServerException
     */
    public function delete($contactId, $identifierId)
    {
        $ResourceName = $this->getResourceNameWithId($contactId) . '/' . $identifierId;
        list($status, , $body) =
            $this->httpClient->performHttpRequest(
                Common\HttpClient::REQUEST_DELETE,
                $ResourceName
            );

        if ($status === Common\HttpClient::HTTP_NO_CONTENT) {
            return true;
        }

        return $this->processRequest($body);
    }

    /**
     * Retrieves all the identifiers form the contact based on its
     * contactId.
     *
     * @param string $contactId
     * @param string[] $parameters
     * @return BaseList|ContactIdentifiers
     * @throws HttpException
     * @throws RequestException
     * @throws ServerException
     * @throws AuthenticateException
     */
    public function getList($contactId, $parameters = array())
    {
        list($status, , $body) = $this->httpClient->performHttpRequest(
            HttpClient::REQUEST_GET,
            $this->getResourceNameWithId($contactId),
            $parameters
        );

        if ($status === self::HTTP_STATUS_OK) {
            $body = json_decode($body);

            $items = $body->items;
            unset($body->items);

            $baseList = new BaseList();
            $baseList->loadFromArray($body);

            $objectName = $this->object;

            foreach ($items as $item) {
                $identifier = new $objectName($this->httpClient);
                $identifier->loadFromArray($item);

                $baseList->items[] = $identifier;
            }

            return $baseList;
        }

        return $this->processRequest($body);
    }

    /**
     * Formats a URL for the Contact API's identifiers endpoint based on the
     * contactId.
     *
     * @param string $id
     *
     * @return string
     */
    private function getResourceNameWithId($id)
    {
        return sprintf(self::RESOURCE_NAME, $id);
    }

    /**
     * Throws an exception if the request if the request has any errors.
     *
     * @param string $body
     *
     * @return self
     *
     * @throws RequestException
     * @throws ServerException
     */
    public function processRequest($body)
    {
        $body = @json_decode($body);

        if ($body === null or $body === false) {
            throw new ServerException('Got an invalid JSON response from the server.');
        }

        if (empty($body->errors)) {
            return $this->object->loadFromArray($body);
        }

        $responseError = new ResponseError($body);

        throw new RequestException(
            $responseError->getErrorString()
        );
    }
}
