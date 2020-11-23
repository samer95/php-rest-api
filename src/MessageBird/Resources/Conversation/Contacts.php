<?php

namespace MessageBird\Resources\Conversation;

use MessageBird\Common\HttpClient;
use MessageBird\Objects\Conversation\Contact;
use MessageBird\Resources\Base;

class Contacts extends Base
{
    const RESOURCE_NAME = 'contacts';

    public function __construct(HttpClient $httpClient)
    {
        parent::__construct($httpClient);

        $this->setObject(new Contact());
        $this->setResourceName(self::RESOURCE_NAME);
    }
}
