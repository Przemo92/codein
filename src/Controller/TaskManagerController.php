<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function PHPUnit\Framework\throwException;

class TaskManagerController extends AbstractController
{
    const STATUS_WAITING = "waiting";
    const STATUS_DONE = "done";
    const STATUS_REJECTED = "rejected";

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

    }

    /**
     * @Route("/create", name="create_task", methods = {"POST"})
     */
    public function create(Request $request): Response
    {
        $task = new Task();

        $task->setTitle($request->request->get('title'));
        $task->setDescription($request->request->get('description'));
        $task->setDeadline($request->request->get('deadline'));
        $task->setStatus(self::STATUS_WAITING);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->redirectToRoute('task_manager');
    }

    /**
     * @Route("/switch-status", name="switch_status", methods = {"POST"})
     */
    public function switchStatus(Request $request): Response
    {
        $waitingTasks = $request->request->get('sort1');
        $doneTasks = $request->request->get('sort2');
        $rejectedTasks = $request->request->get('sort3');

        $this->changeTaskStatus($waitingTasks, self::STATUS_WAITING);
        $this->changeTaskStatus($doneTasks, self::STATUS_DONE);
        $this->changeTaskStatus($rejectedTasks, self::STATUS_REJECTED);

        $this->entityManager->flush();
        return new Response(
            'There are no jobs in the database',
            Response::HTTP_OK
        );
    }

    /**
     * @Route("/delete/{id}", name="delete")
     */
    public function delete($id): Response
    {
        $taskToDelete = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
        $this->entityManager->remove($taskToDelete);
        $this->entityManager->flush();

        return $this->redirectToRoute('task_manager');
    }
    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function editionForm($id): Response
    {
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
        //dd($task);
        $relatedTasks = $this->getDoctrine()->getRepository(Task::class)->findBy(["parent" => $id]);

        return $this->render("editForm.html.twig", [
            'task' => $task,
            'related_tasks' => $relatedTasks
        ]);
    }
    /**
     * @Route("/list", name="task_manager")
     */
    public function test(): Response
    {
        $tasksWithStatusWaiting = $this->getDoctrine()->getRepository(Task::class)->findBy(['status' => self::STATUS_WAITING]);
        $tasksWithStatusDone = $this->getDoctrine()->getRepository(Task::class)->findBy(['status' => self::STATUS_DONE]);
        $tasksWithStatusRejected = $this->getDoctrine()->getRepository(Task::class)->findBy(['status' => self::STATUS_REJECTED]);
        return $this->render("test.html.twig", [
            'tasksWithStatusWaiting' => $tasksWithStatusWaiting,
            'tasksWithStatusDone' => $tasksWithStatusDone,
            'tasksWithStatusRejected' => $tasksWithStatusRejected,
        ]);
    }
    /**
     * @Route("/edit-task/{id}", name="edit_task", methods = {"POST"})
     */
    public function edit(Request $request, $id): Response
    {
        $taskToEdit= $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
        $taskToEdit->setTitle($request->request->get('title'));
        $taskToEdit->setDescription($request->request->get('description'));
        $taskToEdit->setDeadline($request->request->get('deadline'));

        $this->saveToDataBase($taskToEdit);

        return $this->redirectToRoute('edit', ['id' => $id]);
    }
    /**
     * @Route("/assign-user/{userId}/{taskId}", name="assign_task_to_user")
     */
    public function assignUser($userId, $taskId): Response
    {
        $taskToEdit= $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $taskId]);
        $taskToEdit->setUser($this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $userId]));

        $this->saveToDataBase($taskToEdit);

        return $this->redirectToRoute('edit', ['id' => $taskId]);
    }
    /**
     * @Route("/delete-assigned-user/{taskId}", name="delete-assigned-user")
     */
    public function deleteAssignedUser($taskId): Response
    {
        $taskToEdit= $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $taskId]);
        $taskToEdit->setUser(NULL);

        $this->saveToDataBase($taskToEdit);

        return $this->redirectToRoute('edit', ['id' => $taskId]);
    }

    /**
     * @Route("/add-related-task/{taskId}", name="add-related-task", methods = {"POST"})
     * @throws \Exception
     */
    public function addRelatedTask(Request $request, $taskId): Response
    {
        $relatedTaskId = $request->request->get('relatedTask');
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $taskId]);
        $relatedTask = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $relatedTaskId]);
        if ($relatedTaskId != $taskId && !$relatedTask->getParent()) {
            $relatedTask->setParent($task);
        } else {
            throw new \Exception("Can not create this relation");
        }

        $this->saveToDataBase($relatedTask);

        return $this->redirectToRoute('edit', ['id' => $taskId]);
    }

    /**
     * @Route("/delete-related-task/{relatedTaskId}{taskId}", name="delete-relation")
     */
    public function deleteRelatedTask($relatedTaskId, $taskId): Response
    {
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $relatedTaskId]);
        $task->setParent(NULL);

        $this->saveToDataBase($task);

        return $this->redirectToRoute('edit', ['id' => $taskId]);
    }
    public function saveToDataBase(Object $task) {
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    public function changeTaskStatus(array $tasks, string $status) {
        if ($tasks) {
            foreach ($tasks as $id) {
                $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
                $task->setStatus($status);
                $this->entityManager->persist($task);
            }
        }
    }
}
