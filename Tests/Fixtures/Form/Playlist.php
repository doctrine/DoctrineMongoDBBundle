<?php

namespace Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Form;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ODM\Document
 */
class Playlist
{
    /**
     * @ODM\Id
     */
    protected $id;

    /**
     * @ODM\ReferenceMany(targetDocument="Video", inversedBy="playlists")
     */
    protected $videos;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
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

    public function getVideos()
    {
        return $this->videos;
    }

    public function setVideos(ArrayCollection $videos)
    {
        foreach ($this->getVideos() as $oldVideo) {
            $this->removeVideo($oldVideo);
        }

        foreach ($videos as $newVideo) {
            $this->addVideo($newVideo);
        }
    }

    public function addVideo(Video $video)
    {
        $this->videos->add($video);
        $video->addPlaylist($this);
    }

    public function removeVideo(Video $video)
    {
        $this->videos->removeElement($video);
        $video->removePlaylist($this);
    }
}
