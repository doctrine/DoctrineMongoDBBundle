<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ODM\Document
 */
class Video
{
    /**
     * @ODM\Id
     */
    protected $id;

    /**
     * @ODM\Int()
     */
    protected $nbPlaylists = 0;

    /**
     * @ODM\ReferenceMany(targetDocument="Playlist", mappedBy="videos")
     */
    protected $playlists;

    public function __construct()
    {
        $this->playlists = new ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->getId();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function addPlaylist(Playlist $playlist)
    {
        $this->playlists->add($playlist);
        $this->nbPlaylists++;
    }

    public function removePlaylist(Playlist $playlist)
    {
        $this->playlists->removeElement($playlist);
        $this->nbPlaylists--;
    }

    public function getPlaylists()
    {
        return $this->playlists;
    }

    public function getNbPlaylists()
    {
        return $this->nbPlaylists;
    }

    public function setNbPlaylists($nbPlaylists)
    {
        $this->nbPlaylists = $nbPlaylists;
    }
}
