<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskManagerController extends AbstractController
{
    const STATUS_WAITING = "waiting";
    const STATUS_DONE = "done";
    const STATUS_REJECTED = "rejected";

    /**
     * @Route("/test", name="test")
     */
    public function index(): Response
    {
        $tasksWithStatusWaiting = $this->getDoctrine()->getRepository(Task::class)->findBy(['status' => 'waiting']);
        $tasksWithStatusDone = $this->getDoctrine()->getRepository(Task::class)->findBy(['status' => 'done']);
        $tasksWithStatusRejected = $this->getDoctrine()->getRepository(Task::class)->findBy(['status' => 'rejected']);
        return $this->render("index.html.twig", [
            'tasksWithStatusWaiting' => $tasksWithStatusWaiting,
            'tasksWithStatusDone' => $tasksWithStatusDone,
            'tasksWithStatusRejected' => $tasksWithStatusRejected,
        ]);
    }

    /**
     * @Route("/create", name="create_task", methods = {"POST"})
     */
    public function create(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $task = new Task();

        $task->setTitle($request->request->get('title'));
        $task->setDescription($request->request->get('description'));
        $task->setDeadline($request->request->get('deadline'));
        $task->setStatus(self::STATUS_WAITING);

        $em->persist($task);
        $em->flush();

        return $this->redirectToRoute('task_manager');
    }

    /**
     * @Route("/switch-status", name="switch_status", methods = {"POST"})
     */
    public function switchStatus(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();

        if ($waitingTasks = $request->request->get('sort1')) {
            foreach ($waitingTasks as $id) {
                $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
                $task->setStatus(self::STATUS_WAITING);
                $em->persist($task);
            }
        }
        if ($doneTasks = $request->request->get('sort2')) {
            foreach ($doneTasks as $id) {
                $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
                $task->setStatus(self::STATUS_DONE);
                $em->persist($task);
            }
        }
        if ($rejectedTasks = $request->request->get('sort3')) {
            foreach ($rejectedTasks as $id) {
                $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
                $task->setStatus(self::STATUS_REJECTED);
                $em->persist($task);
            }
        }
        $em->flush();
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
        $em = $this->getDoctrine()->getManager();
        $taskToDelete = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
        $em->remove($taskToDelete);
        $em->flush();

        return $this->redirectToRoute('task_manager');
    }
    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function editionForm($id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
        //dd($task);
        return $this->render("editForm.html.twig", ['task' => $task]);
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
        $em = $this->getDoctrine()->getManager();
        $taskToEdit= $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);
        $taskToEdit->setTitle($request->request->get('title'));
        $taskToEdit->setDescription($request->request->get('description'));
        $taskToEdit->setDeadline($request->request->get('deadline'));

        $em->persist($taskToEdit);
        $em->flush();

        return $this->redirectToRoute('task_manager');
    }
    /**
     * @Route("/assign-user/{userId}/{taskId}", name="assign_task_to_user")
     */
    public function assignUser(Request $request, $userId, $taskId): Response
    {
        $em = $this->getDoctrine()->getManager();
        $taskToEdit= $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $taskId]);
        $taskToEdit->setUser($this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $userId]));

        $em->persist($taskToEdit);
        $em->flush();

        return $this->redirectToRoute('edit', ['id' => $taskId]);
    }
    /**
     * @Route("/delete-assigned-user/{taskId}", name="delete-assigned-user")
     */
    public function deleteAssignedUser(Request $request, $taskId): Response
    {
        $em = $this->getDoctrine()->getManager();
        $taskToEdit= $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $taskId]);
        $taskToEdit->setUser(NULL);

        $em->persist($taskToEdit);
        $em->flush();

        return $this->redirectToRoute('edit', ['id' => $taskId]);
    }
    /**
     * @Route("/add-related-task/{taskId}", name="add_related_task", methods = {"POST"})
     */
    public function addRelatedTask(Request $request, $taskId): Response
    {
        $em = $this->getDoctrine()->getManager();
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $taskId]);
        $relatedTaskId = $request->request->get('relatedTask');
        $task->setTask($this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $relatedTaskId]));

        $em->persist($task);
        $em->flush();

        return $this->redirectToRoute('edit', ['id' => $taskId]);
    }
}
