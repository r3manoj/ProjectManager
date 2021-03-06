<?php

use Phalcon\Mvc\Model\Validator\Uniqueness as UniquenessValidator;

class Project extends Phalcon\Mvc\Model
{
	public function validation()
    {
        $this->validate(new UniquenessValidator(array(
            'field' => 'name',
            'message' => 'The Project already registered.',
        )));

        if ($this->validationHasFailed() == true) {
            return false;
        }
    }

    public function initialize()
    {
        $this->hasMany('id', 'ProjectUser', 'project_id');
        $this->hasMany('id', 'Task', 'project_id');
        $this->hasMany('id', 'Note', 'project_id');
    }

    public function isOwner($user, $inclAdmin=true)
    {
        if ($this->created_by == $user->id) {
            return true;
        }

        if ($inclAdmin) {
            if ($user->role_id == 1) {
                return true;
            }
        }

        return false;
    }

    public function isInProject($user)
    {
        $adminUsers = User::find('role_id="' . 1 . '"');

        foreach($adminUsers AS $adminUser) {
            // Let's check whether the user is already present in the project.
            $projectUser = null;
            $projectUser = ProjectUser::findFirst('project_id="' . $this->id . '" AND user_id="' . $adminUser->id . '"');

            if (!$projectUser) {
                $projectUser = new ProjectUser();
                $projectUser->user_id = $adminUser->id;
                $projectUser->project_id = $this->id;
                $projectUser->created_at = new Phalcon\Db\RawValue('now()');

                $projectUser->save();
            }
        }

        $projectUser = ProjectUser::findFirst('user_id="' . $user->id . '" AND project_id="' . $this->id . '"');

        if ($projectUser) {
            return true;
        }

        return false;
    }

    public function getDevelopers($inclAdmin=true)
    {
        $users = array();

        $projectUsers = ProjectUser::find('project_id="' . $this->id . '"');

        foreach ($projectUsers AS $projectUser) {
            $user = $projectUser->getUser();

            if ($user->role_id == 3) {
                continue;
            }

            if (!$inclAdmin) {
                if ($user->role_id == 1) {
                    continue;
                }
            }

            $users[] = $user;
        }

        return $users;
    }

    public function getTasks($limit=null)
    {
        if (!is_null($limit)) {
            $tasks = Task::find(array(
                'conditions' => 'project_id=' . $this->id,
                'order' => 'status ASC, created_at DESC',
                'limit' => $limit,
            ));
        }
        else {
            $tasks = Task::find(array(
                'conditions' => 'project_id=' . $this->id,
                'order' => 'status ASC, created_at DESC',
            ));
        }

        if (count($tasks) > 0) {
            return $tasks;
        }

        return null;
    }

    public function getOpenTasks($limit=null)
    {
        if (!is_null($limit)) {
            $tasks = Task::find(array(
                'conditions' => 'project_id=' . $this->id . ' AND status=0',
                'order' => 'status ASC, created_at DESC',
                'limit' => $limit,
            ));
        }
        else {
            $tasks = Task::find(array(
                'conditions' => 'project_id=' . $this->id . ' AND status=0',
                'order' => 'status ASC, created_at DESC',
            ));
        }

        if (count($tasks) > 0) {
            return $tasks;
        }

        return null;
    }

    public function getNotes($limit=null)
    {
        if (!is_null($limit)) {
            $notes = Note::find(array(
                'conditions' => 'project_id=' . $this->id,
                'order' => 'created_at DESC',
                'limit' => $limit,
            ));
        }
        else {
            $notes = Note::find(array(
                'conditions' => 'project_id=' . $this->id,
                'order' => 'created_at DESC',
            ));
        }

        if (count($notes) > 0) {
            return $notes;
        }

        return null;
    }
}
