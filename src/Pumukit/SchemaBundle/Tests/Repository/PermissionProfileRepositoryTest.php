<?php

namespace Pumukit\SchemaBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class PermissionProfileRepositoryTest extends WebTestCase
{
    private $dm;
    private $repo;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();
        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->repo = $this->dm
            ->getRepository('PumukitSchemaBundle:PermissionProfile');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:PermissionProfile')
            ->remove(array());
    }

    public function testEmpty()
    {
        $this->assertEmpty($this->repo->findAll());
    }

    public function testRepository()
    {
        $this->assertCount(0, $this->repo->findAll());

        $permissionProfile = new PermissionProfile();
        $permissionProfile->setName('test');

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        $this->assertCount(1, $this->repo->findAll());

        $this->assertEquals($permissionProfile, $this->repo->find($permissionProfile->getId()));
    }

    public function testChangeDefault()
    {
        $this->assertCount(0, $this->repo->findByDefault(true));
        $this->assertCount(0, $this->repo->findByDefault(false));

        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setDefault(true);

        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setDefault(false);

        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setDefault(false);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        $this->assertCount(1, $this->repo->findByDefault(true));
        $this->assertCount(2, $this->repo->findByDefault(false));

        $this->repo->changeDefault();

        $this->assertCount(0, $this->repo->findByDefault(true));
        $this->assertCount(3, $this->repo->findByDefault(false));

        $this->repo->changeDefault(false);

        $this->assertCount(3, $this->repo->findByDefault(true));
        $this->assertCount(0, $this->repo->findByDefault(false));

        $this->repo->changeDefault(true);

        $this->assertCount(0, $this->repo->findByDefault(true));
        $this->assertCount(3, $this->repo->findByDefault(false));
    }

    public function testFindDefaultCandidate()
    {
        $this->assertNull($this->repo->findDefaultCandidate());

        $permissions1 = array(Permission::ACCESS_DASHBOARD, Permission::ACCESS_LIVE_CHANNELS);
        $permissionProfile1 = new PermissionProfile();
        $permissionProfile1->setName('test1');
        $permissionProfile1->setPermissions($permissions1);

        $permissions2 = array();
        $permissionProfile2 = new PermissionProfile();
        $permissionProfile2->setName('test2');
        $permissionProfile2->setPermissions($permissions2);

        $permissions3 = array(Permission::ACCESS_DASHBOARD);
        $permissionProfile3 = new PermissionProfile();
        $permissionProfile3->setName('test3');
        $permissionProfile3->setPermissions($permissions3);

        $this->dm->persist($permissionProfile1);
        $this->dm->persist($permissionProfile2);
        $this->dm->persist($permissionProfile3);
        $this->dm->flush();

        $this->assertEmpty($this->repo->findByDefault(true));
        $this->assertNotEmpty($this->repo->findByDefault(false));

        $this->assertEquals($permissionProfile2, $this->repo->findDefaultCandidate());
    }
}