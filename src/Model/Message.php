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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     */
    public function setToUser(UserModel $toUser)
    {
        $this->toUser = $toUser;
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
     */
    public function setFromUser(UserModel $fromUser)
    {
        $this->fromUser = $fromUser;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}