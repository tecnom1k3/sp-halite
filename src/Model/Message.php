<?php
namespace Acme\Model;

use Acme\Model\User as UserModel;

/**
 * @Entity(repositoryClass="Acme\Model\Repository\Message")
 * @Table(name="messages")
 **/
class Message
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var UserModel
     * @ManyToOne(targetEntity="Acme\Model\User")
     * @JoinColumn(name="users_id", referencedColumnName="id")
     */
    protected $toUser;

    /**
     * @var UserModel
     * @ManyToOne(targetEntity="Acme\Model\User")
     * @JoinColumn(name="fromUserId", referencedColumnName="id")
     */
    protected $fromUser;

    /**
     * @var string
     * @Column(type="blob")
     */
    protected $subject;

    /**
     * @var string
     * @Column(type="blob")
     */
    protected $message;
    
    /**
     * @var string
     * @Column(type="blob")
     */
    protected $mac;

    /**
     * @return resource
     */
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * @param string $mac
     */
    public function setMac($mac)
    {
        $this->mac = $mac;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return UserModel
     */
    public function getToUser()
    {
        return $this->toUser;
    }

    /**
     * @param UserModel $toUser
     * @return $this
     */
    public function setToUser(UserModel $toUser)
    {
        $this->toUser = $toUser;
        return $this;
    }

    /**
     * @return UserModel
     */
    public function getFromUser()
    {
        return $this->fromUser;
    }

    /**
     * @param UserModel $fromUser
     * @return $this
     */
    public function setFromUser(UserModel $fromUser)
    {
        $this->fromUser = $fromUser;
        return $this;
    }

    /**
     * @return resource
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return resource
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}