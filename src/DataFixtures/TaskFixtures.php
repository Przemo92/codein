<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $names = ['First', 'Second', 'Third'];
        $parentIds = [1,2,3];
        $this->loadMainTasks($manager);
        foreach (array_combine($parentIds, $names) as $parentId => $name) {
            $this->loadSubTasks($manager, $name, $parentId);
        }
    }

    private function loadMainTasks(ObjectManager $manager)
    {
        foreach ($this->getTaskData() as $title) {
            $task = new Task();
            $task->setTitle($title);
            $task->setDescription('Lorem ipsum');
            $task->setStatus('waiting');
            $manager->persist($task);
        }
        $manager->flush();
    }

    private function loadSubTasks(ObjectManager $manager, string $name, int $parentId)
    {
        $parent = $manager->getRepository(Task::class)->find($parentId);
        $methodName = "getSubTaskDataFor{$name}Task";
        foreach ($this->$methodName() as $title) {

            $task = new Task();
            $task->setTitle($title);
            $task->setDescription('Lorem ipsum sub');
            $task->setStatus('waiting');
            $task->setParent($parent);
            $manager->persist($task);
        }
        $manager->flush();
    }
    private function getTaskData(): array
    {
        return ['First', 'Second', 'Third', 'Fourth'];
    }

    private function getSubTaskDataForFirstTask(): array
    {
        return ['first1', 'second1', 'third1', 'fourth1'];
    }
    private function getSubTaskDataForSecondTask(): array
    {
        return ['first2', 'second2', 'third2', 'fourth2'];
    }
    private function getSubTaskDataForThirdTask(): array
    {
        return ['first3', 'second3', 'third3', 'fourth3'];
    }
}
