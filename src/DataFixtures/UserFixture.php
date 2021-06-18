<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserFixture extends Fixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    public function load(ObjectManager $manager)
    {
         $user = new User();
         $user->setEmail('dd@wp.pl');
         $hash = $this->container->get('security.password_encoder')->encodePassword($user, 'admin');
         $user->setPassword($hash);
         $user->setRoles(['ROLE_ADMIN']);
         $manager->persist($user);

        $manager->flush();
    }
}
