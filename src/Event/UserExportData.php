<?php

namespace WebEtDesign\UserBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use WebEtDesign\MailerBundle\Attribute\MailEvent;

#[MailEvent(
    name: self::NAME,
    label: 'Export données utilisateur',
    subject: 'Export de vos données',
    templateHtml: '@WDUser/emails/USER_EXPORT_DATA.html.twig',
    templateText: '@WDUser/emails/USER_EXPORT_DATA.txt.twig'
)]
class UserExportData extends Event
{
    public const NAME = 'USER_EXPORT_DATA';

    private $user;

    private ?string $data = '';
    
    public function __construct($user, ?string $data)
    {
        $this->user = $user;
        $this->data = $data;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return string|null
     */
    public function getData(bool $toArray = false)
    {
        $data = json_decode($this->data, true);

        if(isset($data['_archive'])){
            unset($data['_archive']);
        }

        return $toArray ? $data : json_encode($data);
    }

    public function getDataArray(){
        return $this->getData(true);
    }

    public function getArchiveLink(){
        $data = json_decode($this->data, true);

        if(isset($data['_archive'])){
            return $data['_archive'];
        }
        return null;
    }

    /**
     * @param string|null $data
     */
    public function setData(?string $data): void
    {
        $this->data = $data;
    }

    public function getLocale(){
        return $this->getUser()->getLocale();
    }
}
