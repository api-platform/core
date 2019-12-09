<?php

/*
 * This file is part of the API Platform project.
 *
 *
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);


namespace ApiPlatform\Core\Mercure;


class EntitiesToPublish
{
    private $createdEntities;
    private $updatedEntities;
    private $deletedEntities;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->createdEntities = new \SplObjectStorage();
        $this->updatedEntities = new \SplObjectStorage();
        $this->deletedEntities = new \SplObjectStorage();
    }

    public function addDeletedEntities($entity, $value){
        $this->deletedEntities[$entity] = $value;
    }

    public function addUpdatedEntities($entity, $value){
        $this->updatedEntities[$entity] = $value;
    }

    public function addCreatedEntities($entity, $value){
        $this->createdEntities[$entity] = $value;
    }

    /**
     * @return mixed
     */
    public function getCreatedEntities()
    {
        return $this->createdEntities;
    }

    /**
     * @return mixed
     */
    public function getUpdatedEntities()
    {
        return $this->updatedEntities;
    }

    /**
     * @return mixed
     */
    public function getDeletedEntities()
    {
        return $this->deletedEntities;
    }

    public function getCreatedEntityValue($entity){
        return $this->createdEntities[$entity];
    }

    public function getUpdatedEntityValue($entity){
        return $this->updatedEntities[$entity];
    }

    public function getDeletedEntityValue($entity){
        return $this->deletedEntities[$entity];
    }
}
