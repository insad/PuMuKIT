<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;

/**
 * MultimediaObjectRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MultimediaObjectRepository extends DocumentRepository
{
    /**
     * Find all multimedia objects in a series with given status
     *
     * @param Series $series
     * @param array $status
     * @return ArrayCollection
     */
    public function findWithStatus(Series $series, array $status)
    {
        return $this->createQueryBuilder()
          ->field('series')->references($series)
          ->field('status')->in($status)
          ->sort('rank', 1)
          ->getQuery()
          ->execute();
    }
    
    /**
     * Find multimedia object prototype
     *
     * @param Series $series
     * @param array $status
     * @return MultimediaObject
     */
    public function findPrototype(Series $series)
    {
        return $this->createQueryBuilder()
          ->field('series')->references($series)
          ->field('status')->equals(MultimediaObject::STATUS_PROTOTYPE)
          ->getQuery()
          ->getSingleResult();
    }

    /**
     * Find multimedia objects in a series
     * without the template (prototype)
     *
     * @param Series $series
     * @return ArrayCollection
     */
    public function findWithoutPrototype(Series $series)
    {
        $aux = $this->createQueryBuilder()
          ->field('series')->references($series)
          ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE)
          ->sort('rank', 1)
          ->getQuery()
          ->execute();
        
        return $aux;
    }

    /**
     * Find multimedia objects by pic id
     *
     * @param string $picId
     * @return MultimediaObject
     */
    public function findByPicId($picId)
    {
        return $this->createQueryBuilder()
          ->field('pics._id')->equals(new \MongoId($picId))
          ->getQuery()
          ->getSingleResult();
    }
    
    /**
     * Find multimedia objects by person id
     *
     * @param string $personId
     * @return ArrayCollection
     */
    public function findByPersonId($personId)
    {
        return $this->createQueryBuilder()
          ->field('people_in_multimedia_object.people._id')->equals(new \MongoId($personId))
          ->getQuery()
          ->execute();
    }

    /**
     * Find multimedia objects by person id
     * with given role
     *
     * @param string $personId
     * @param string $roleCod
     * @return ArrayCollection
     */
    public function findByPersonIdWithRoleCod($personId, $roleCod)
    {
        $qb = $this->createQueryBuilder();
        $qb->field('people_in_multimedia_object')->elemMatch(
            $qb->expr()->field('people._id')->equals(new \MongoId($personId))
                ->field('cod')->equals($roleCod)
        );

        return $qb->getQuery()->execute();
    }

    /**
     * Find series by person id
     *
     * @param string $personId
     * @return ArrayCollection
     */
    public function findSeriesFieldByPersonId($personId)
    {
        return $this->createQueryBuilder()
          ->field('people_in_multimedia_object.people._id')->equals(new \MongoId($personId))
          ->distinct('series')
          ->getQuery()
          ->execute();
    }


    // Find Multimedia Objects with Tags

    /**
     * Find multimedia objects by tag id
     *
     * @param Tag|EmbeddedTag $tag
     * @param array $sort
     * @param int $limit
     * @param int $page
     * @return ArrayCollection
     */
    public function findWithTag($tag, $sort = array(), $limit = 0, $page = 0)
    {
        $qb = $this->createBuilderWithTag($tag, $sort);

        if ($limit > 0){
            $qb->limit($limit)->skip($limit * $page);
        }

        return $qb->getQuery()->execute();
    }


    /**
     * Create QueryBuilder to find multimedia objects by tag id
     *
     * @param Tag|EmbeddedTag $tag
     * @param array $sort
     * @return QueryBuilder
     */
    public function createBuilderWithTag($tag, $sort = array())
    {
        $qb = $this->createStandardQueryBuilder()
            ->field('tags._id')->equals(new \MongoId($tag->getId()));
        
        if (0 !== count($sort) ){
            $qb->sort($sort['fieldName'], $sort['order']);
        }        

        return $qb;
    }

    /**
     * Find one multimedia object by tag id
     *
     * @param Tag|EmbeddedTag $tag
     * @return MultimediaObject
     */
    public function findOneWithTag($tag)
    {
        return $this->createStandardQueryBuilder()
          ->field('tags._id')->equals(new \MongoId($tag->getId()))
          ->getQuery()
          ->getSingleResult();
    }

    /**
     * Find multimedia objects with any tag
     *
     * @param array $tags
     * @param array $sort
     * @param int $limit
     * @param int $page
     * @return ArrayCollection
     */
    public function findWithAnyTag($tags, $sort = array(), $limit = 0, $page = 0)
    {
        $mongoIds = $this->getMongoIds($tags);
        $qb =  $this->createStandardQueryBuilder()
          ->field('tags._id')->in($mongoIds);
        
        if (0 !== count($sort) ){
          $qb->sort($sort['fieldName'], $sort['order']);
        }        
        
        if ($limit > 0){
            $qb->limit($limit)->skip($limit * $page);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Find multimedia objects with all tags
     *
     * @param array $tags
     * @param array $sort
     * @param int $limit
     * @param int $page
     * @return ArrayCollection
     */
    public function findWithAllTags($tags, $sort = array(), $limit = 0, $page = 0)
    {
        $mongoIds = $this->getMongoIds($tags);
        $qb =  $this->createStandardQueryBuilder()
          ->field('tags._id')->all($mongoIds);
        
        if (0 !== count($sort) ){
            $qb->sort($sort['fieldName'], $sort['order']);
        }        

        if ($limit > 0){
            $qb->limit($limit)->skip($limit * $page);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Find one multimedia object with all tags
     *
     * @param array $tags
     * @return MultimediaObject
     */
    public function findOneWithAllTags($tags)
    {
        $mongoIds = $this->getMongoIds($tags);
        $qb =  $this->createStandardQueryBuilder()
          ->field('tags._id')->all($mongoIds);
        
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Find multimedia objects without tag id
     *
     * @param Tag|EmbeddedTag $tag
     * @param array $sort
     * @param int $limit
     * @param int $page
     * @return ArrayCollection
     */
    public function findWithoutTag($tag, $sort = array(), $limit = 0, $page = 0)
    {
        $qb =  $this->createStandardQueryBuilder()
          ->field('tags._id')->notEqual(new \MongoId($tag->getId()));
        
        if (0 !== count($sort) ){
            $qb->sort($sort['fieldName'], $sort['order']);
        }        

        if ($limit > 0){
            $qb->limit($limit)->skip($limit * $page);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Find one multimedia object without tag id
     *
     * @param Tag|EmbeddedTag $tag
     * @return MultimediaObject
     */
    public function findOneWithoutTag($tag)
    {
        return $this->createStandardQueryBuilder()
          ->field('tags._id')->notEqual(new \MongoId($tag->getId()))
          ->getQuery()
          ->getSingleResult();
    }

    /**
     * Find multimedia objects without all tags
     *
     * @param array $tags
     * @param array $sort
     * @param int $limit
     * @param int $page
     * @return ArrayCollection
     */
    public function findWithoutAllTags($tags, $sort = array(), $limit = 0, $page = 0)
    {
        $mongoIds = $this->getMongoIds($tags);
        $qb =  $this->createStandardQueryBuilder()
          ->field('tags._id')->notIn($mongoIds);
        
        if (0 !== count($sort) ){
            $qb->sort($sort['fieldName'], $sort['order']);
        }        

        if ($limit > 0){
            $qb->limit($limit)->skip($limit * $page);
        }

        return $qb->getQuery()->execute();
    }

    // End of find Multimedia Objects with Tags

    // Find Series Field with Tags

    /**
     * Find series with tag
     *
     * @param Tag|EmbeddedTag $tag
     * @return ArrayCollection
     */
    public function findSeriesFieldWithTag($tag)
    {
        return $this->createStandardQueryBuilder()
            ->field('tags._id')->equals(new \MongoId($tag->getId()))
            ->distinct('series')
            ->getQuery()->execute();
    }

    /**
     * Find one series with tag
     *
     * @param Tag|EmbeddedTag $tag
     * @return Series
     */
    public function findOneSeriesFieldWithTag($tag)
    {
        return $this->createStandardQueryBuilder()
          ->field('tags._id')->equals(new \MongoId($tag->getId()))
          ->distinct('series')
          ->getQuery()
          ->getSingleResult();
    }

    /**
     * Find series with any tag
     *
     * @param array $tags
     * @return ArrayCollection
     */
    public function findSeriesFieldWithAnyTag($tags)
    {
        $mongoIds = $this->getMongoIds($tags);

        return $this->createStandardQueryBuilder()
            ->field('tags._id')->in($mongoIds)
            ->distinct('series')
            ->getQuery()->execute();
    }

    /**
     * Find series with all tags
     *
     * @param array $tags
     * @return ArrayCollection
     */
    public function findSeriesFieldWithAllTags($tags)
    {
        $mongoIds = $this->getMongoIds($tags);

        return  $this->createStandardQueryBuilder()
            ->field('tags._id')->all($mongoIds)
            ->distinct('series')
            ->getQuery()->execute();
    }

    /**
     * Find one series with all tags
     *
     * @param array $tags
     * @return Series
     */
    public function findOneSeriesFieldWithAllTags($tags)
    {
        $mongoIds = $this->getMongoIds($tags);

        return  $this->createStandardQueryBuilder()
            ->field('tags._id')->all($mongoIds)
            ->distinct('series')
            ->getQuery()
            ->getSingleResult();
    }

    // End of find Series with Tags

    /**
     * Find distinct url pics in series
     *
     * TODO Limit and sort
     *
     * @param Series $series
     * @return ArrayCollection
     */
    public function findDistinctUrlPicsInSeries(Series $series)
    {
        return $this->createStandardQueryBuilder()
          ->field('series')->references($series)
          ->distinct('pics.url')
          ->getQuery()
          ->execute();
    }

    /**
     * Find distinct url pics
     *
     * TODO Limit and sort
     *
     * @return ArrayCollection
     */
    public function findDistinctUrlPics()
    {
        return $this->createStandardQueryBuilder()
          ->distinct('pics.url')
          ->sort('public_date', 1)
          ->getQuery()
          ->execute();
    }

    /**
     * Find by series
     *
     * @param Series $series
     * @return ArrayCollection
     */
    public function findBySeries(Series $series)
    {
        return $this->createQueryBuilder()
          ->field('series')->references($series)
          ->getQuery()
          ->execute();
    }

    /**
     * Find by broadcast
     *
     * @param Broadcast $broadcast
     * @return ArrayCollection
     */
    public function findByBroadcast(Broadcast $broadcast)
    {
        return $this->createQueryBuilder()
          ->field('broadcast')->references($broadcast)
          ->getQuery()
          ->execute();
    }

    /**
     * Find ordered by fieldName: asc/desc
     *
     * @param Series $series
     * @param array $sort
     * @return QueryBuilder
     */
    public function getQueryBuilderOrderedBy(Series $series, $sort = array())
    {
        $qb = $this->createStandardQueryBuilder()
          ->field('series')->references($series);
        if (0 !== count($sort)) $qb->sort($sort['fieldName'], $sort['order']);
        return $qb;
    }


    /**
     * Find ordered by fieldName: asc/desc
     *
     * @param Series $series
     * @param array $sort
     * @return Cursor
     */
    public function findOrderedBy(Series $series, $sort = array())
    {
        $qb = $this->getQueryBuilderOrderedBy($series, $sort);
        return $qb->getQuery()->execute();
    }

    /**
     * Get mongo ids
     *
     * @param array $documents
     * @return array
     */
    private function getMongoIds($documents)
    {
        $mongoIds = array();
        foreach($documents as $document){
            $mongoIds[] = new \MongoId($document->getId());
        }

        return $mongoIds;
    }

    /**
     * Create standard query builder
     *
     * Creates a query builder with all multimedia objects
     * having status different than PROTOTYPE.
     * These are the multimedia objects we need to show
     * in series.
     * 
     * @return QueryBuilder
     */
    public function createStandardQueryBuilder()
    {
        return $this->createQueryBuilder()
          ->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE);
    }
}
