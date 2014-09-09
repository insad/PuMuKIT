<?php
namespace Pumukit\SchemaBundle\Test\Document;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;

class SeriesRepositoryTest extends WebTestCase
{
	private $dm;
	private $repo;

	public function setUp()
	{
		$options = array('environment' => 'test');
		$kernel = static::createKernel($options);
		$kernel->boot();
		$this->dm = $kernel->getContainer()
			->get('doctrine_mongodb')->getManager();
		$this->repo = $this->dm
			->getRepository('PumukitSchemaBundle:Series');
		
		/*
		//DELETE DATABASE - pimo has to be deleted before mm
		$this->dm->getDocumentCollection('PumukitSchemaBundle:PersonInMultimediaObject')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:Role')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:Person')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:Series')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:SeriesType')
			->remove(array());
		$this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')
			->remove(array());
		$this->dm->flush();
		*/
	}

	public function testRepositoryEmpty()
	{
		$this->assertEquals(0, count($this->repo->findAll()));
	}

	// TO DO: test proper time sorting
	/*
	public function testFindSeriesByTags()
	{
		// Only one SeriesType at the moment.
		$series_type = $this->createSeriesType("Medieval Fantasy Sitcom");

		$series_main = $this->createSeries("Stark's growing pains");
		$series_white = $this->createSeries("White Walkers adventures");
		$series_wall = $this->createSeries("The Wall");
		$series_lhazar = $this->createSeries("A quiet life in Lhazar");
		$series_type->addSeries($series_main);
		$series_type->addSeries($series_white);
		$series_type->addSeries($series_wall);
		$series_type->addSeries($series_lhazar);

		$this->em->persist($series_type);
		$this->em->persist($series_main);
		$this->em->persist($series_white);
		$this->em->persist($series_wall);
		$this->em->persist($series_lhazar);

		$tag_root = new Tag("Game of Matterhorns");

		$tag_r_metatag = new Tag("Regions metatag");
		$tag_r_wall = new Tag("The Wall region");
		$tag_r_north = new Tag("The North region");
		$tag_r_essos = new Tag("Essos region");
		$tag_r_metatag->setMetatag(true);
		$tag_r_metatag->setParent($tag_root);
		$tag_r_wall->setParent($tag_r_metatag);
		$tag_r_north->setParent($tag_r_metatag);
		$tag_r_essos->setParent($tag_r_metatag);

		$tag_h_metatag = new Tag("Great Houses metatag");
		$tag_h_stark = new Tag("Stark House");
		$tag_h_night = new Tag("Night's Watch House");
		$tag_h_khalasar = new Tag("Dhrogo Khalasar House");

		$tag_h_metatag->setMetatag(true);
		$tag_h_metatag->setParent($tag_root);
		$tag_h_stark->setParent($tag_h_metatag);
		$tag_h_night->setParent($tag_h_metatag);
		$tag_h_khalasar->setParent($tag_h_metatag);

		$tag_g_metatag = new Tag("Genres metatag");
		$tag_g_defence = new Tag("Defense proceeding genre");
		$tag_g_raven = new Tag("Carrier raven reading genre");
		$tag_g_unused = new Tag("Unused genre");

		$tag_g_metatag->setMetatag(true);
		$tag_g_metatag->setParent($tag_root);
		$tag_g_defence->setParent($tag_g_metatag);
		$tag_g_raven->setParent($tag_g_metatag);
		$tag_g_unused->setParent($tag_g_metatag);

		$this->em->persist($tag_root);
		$this->em->persist($tag_r_metatag);
		$this->em->persist($tag_h_metatag);
		$this->em->persist($tag_g_metatag);
		$this->em->persist($tag_r_wall);
		$this->em->persist($tag_r_north);
		$this->em->persist($tag_r_essos);
		$this->em->persist($tag_h_stark);
		$this->em->persist($tag_h_night);
		$this->em->persist($tag_h_khalasar);
		$this->em->persist($tag_g_defence);
		$this->em->persist($tag_g_raven);
		$this->em->persist($tag_g_unused);

		$mm1=$this->createMultimediaObjectAssignedToSeries ('MmObject 1', $series_main);
		$mm2=$this->createMultimediaObjectAssignedToSeries ('MmObject 2', $series_main);

		$mm3=$this->createMultimediaObjectAssignedToSeries ('MmObject 3', $series_white);
		$mm4=$this->createMultimediaObjectAssignedToSeries ('MmObject 4', $series_white);

		$mm5=$this->createMultimediaObjectAssignedToSeries ('MmObject 5', $series_wall);
		$mm6=$this->createMultimediaObjectAssignedToSeries ('MmObject 6', $series_wall);

		$mm7=$this->createMultimediaObjectAssignedToSeries ('MmObject 7', $series_lhazar);
		$mm8=$this->createMultimediaObjectAssignedToSeries ('MmObject 8', $series_lhazar);

		$mm1->setTags(array($tag_r_wall, $tag_r_north, $tag_r_essos,
					$tag_h_stark, $tag_h_night, $tag_h_khalasar,
					$tag_g_defence, $tag_g_raven));

		$mm2->addTag($tag_r_north);
		$mm2->addTag($tag_h_stark);
		$mm2->addTag($tag_g_raven);

		$mm3->addTag($tag_r_wall);

		$mm5->setTags(array($tag_r_wall, $tag_r_north, $tag_h_stark, $tag_h_night,
					$tag_g_raven));
		$mm6->setTags(array($tag_r_wall, $tag_h_night, $tag_g_defence));

		$mm7->setTags(array($tag_r_essos, $tag_h_khalasar, $tag_g_raven));
		$mm8->setTags(array($tag_r_essos, $tag_h_khalasar));

		$this->em->persist($mm1);
		$this->em->persist($mm2);
		$this->em->persist($mm3);
		$this->em->persist($mm4);
		$this->em->persist($mm5);
		$this->em->persist($mm6);
		$this->em->persist($mm7);
		$this->em->persist($mm8);

		$this->em->flush();

		$this->assertEquals(0, count($this->repo->findByTag($tag_g_unused)));

		// findOneByTag uses query->getSingleResult and throws an exception
		// if no result is found. See "Manual de Symfony2, Release 2.0.1" p.124
		try {
			$testNoResultException = $this->repo->findOneByTag($tag_g_unused);
		} catch (\Doctrine\ORM\NoResultException $e) {
			$testNoResultException = true;
		}
		$this->assertTrue($testNoResultException);
		unset ($testNoResultException);

		$this->assertEquals(1, count($this->repo->findOneByTag($tag_r_north)));
		$this->assertEquals(1, count($this->repo->findOneByTag($tag_r_essos)));
		$this->assertEquals($series_main, $this->repo->findOneByTag($tag_r_north));

		// Test findByTag
		$this->assertEquals(2, count($this->repo->findByTag($tag_r_north)));
		$this->assertEquals(2, count($this->repo->findByTag($tag_r_essos)));
		$this->assertEquals(2, count($this->repo->findByTag($tag_h_night)));
		$this->assertEquals(2, count($this->repo->findByTag($tag_h_khalasar)));
		$this->assertEquals(2, count($this->repo->findByTag($tag_g_defence)));
		$this->assertEquals(3, count($this->repo->findByTag($tag_g_raven)));

		// Test findWithAnyTag
		$this->assertEquals (2,count($this->repo->findWithAnyTag(array($tag_g_defence))));
		$this->assertEquals (3,count($this->repo->findWithAnyTag(array(
							$tag_g_defence, $tag_g_raven))));

		// Test findWithAllTags
		$this->assertEquals (3,count($this->repo->findWithAllTags(array(
							$tag_g_raven))));
		$this->assertEquals (1,count($this->repo->findWithAllTags(array(
							$tag_g_defence, $tag_g_raven))));
		$this->assertEquals (2,count($this->repo->findWithAllTags(array(
							$tag_r_essos, $tag_h_khalasar, $tag_g_raven))));
		$this->assertEquals (0, count($this->repo->findWithAllTags(array(
							$tag_r_essos, $tag_h_khalasar, $tag_g_raven, $tag_g_unused))));
		$this->assertEquals (1,count($this->repo->findWithAllTags(array(
							$tag_r_north, $tag_r_wall, $tag_r_essos,
							$tag_h_stark, $tag_h_night, $tag_h_khalasar,
							$tag_g_defence, $tag_g_raven))));
		$this->assertEquals (2,count($this->repo->findWithAllTags(array(
							$tag_r_wall, $tag_h_night, $tag_g_defence))));

		// Test findOneWithAllTags
		$this->assertEquals ($series_main, $this->repo->findOneWithAllTags(array(
						$tag_g_defence, $tag_g_raven)));

		try {
			$testNoResultException = $this->repo->findOneWithAllTags(array(
						$tag_g_unused));
		} catch (\Doctrine\ORM\NoResultException $e) {
			$testNoResultException = true;
		}
		$this->assertTrue($testNoResultException);
		unset ($testNoResultException);

		// Test findOneWithoutTag
		$this->assertEquals (1, count($this->repo->findOneWithoutTag($tag_g_raven)));

		// Test findWithoutTag
		$prueba = $this->repo->findWithoutTag($tag_g_raven);
		$this->assertEquals (1, count($prueba));
		$this->assertEquals ($series_white, $prueba[0]);
		unset($prueba);
		$prueba2 = $this->repo->findWithoutTag($tag_h_night);
		$this->assertEquals (2, count($prueba2));
		$this->assertTrue (in_array($series_white, $prueba2));
		$this->assertTrue (in_array($series_lhazar, $prueba2));
		unset($prueba2);
		$this->assertEquals (4, count($this->repo->findWithoutTag($tag_g_unused)));

		// Test findWithoutSomeTags
		$prueba = $this->repo->findWithoutSomeTags(array($tag_g_raven, $tag_g_unused));
		$this->assertEquals ($series_white, $prueba[0]);
		$this->assertEquals (1, count($prueba));
		unset($prueba);
		$prueba2 = $this->repo->findWithoutSomeTags(array($tag_r_essos, $tag_h_khalasar));
		$this->assertEquals (2, count($prueba2));
		$this->assertTrue (in_array($series_white, $prueba2));
		$this->assertTrue (in_array($series_wall, $prueba2));
		unset($prueba2);

		// Test findWithoutAllTags
		$prueba2 = $this->repo->findWithoutAllTags(array(
					$tag_r_wall, $tag_h_night, $tag_g_defence));
		$this->assertEquals (2, count($prueba2));
		$this->assertTrue (in_array($series_white, $prueba2));
		$this->assertTrue (in_array($series_lhazar, $prueba2));
		unset($prueba2);

	}*/

	private function createSeriesType($name)
	{
		$description = 'description';
		$series_type = new SeriesType();

		$series_type->setName($name);
		$series_type->setDescription($description);

		$this->em->persist($series_type);

		return $series_type;
	}

	private function createSeries($title)
	{
		$subtitle = 'subtitle';
		$description = 'description';
		$test_date = new \DateTime("now");
		$serie = new Series();

		$serie->setTitle($title);
		$serie->setSubtitle($subtitle);
		$serie->setDescription($description);
		$serie->setPublicDate($test_date);

		$this->em->persist($serie);

		return $serie;
	}

	private function createMultimediaObjectAssignedToSeries($title, Series $series)
	{
		$status = MultimediaObject::STATUS_NORMAL;
		$record_date = new \DateTime();
		$public_date = new \DateTime();
		$subtitle = 'Subtitle';
		$description = "Description";
		$duration = 123;

		$mm = new MultimediaObject();
		$series->addMultimediaObject($mm);

		// $mm->addTag($tag1);
		// $mm->addTrack($track1);
		// $mm->addPic($pic1);
		// $mm->addMaterial($material1);

		$mm->setStatus($status);
		$mm->setRecordDate($record_date);
		$mm->setPublicDate($public_date);
		$mm->setTitle($title);
		$mm->setSubtitle($subtitle);
		$mm->setDescription($description);
		$mm->setDuration($duration);
		// $this->em->persist($track1);
		// $this->em->persist($pic1);
		// $this->em->persist($material1);
		$this->em->persist($mm);

		return $mm;
	}

}
