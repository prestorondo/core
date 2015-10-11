<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Group;
use Flarum\Core\Repository\GroupRepository;
use Flarum\Event\GroupWillBeSaved;
use Flarum\Core\Support\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;

class EditGroupHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param Dispatcher $events
     * @param GroupRepository $groups
     */
    public function __construct(Dispatcher $events, GroupRepository $groups)
    {
        $this->events = $events;
        $this->groups = $groups;
    }

    /**
     * @param EditGroup $command
     * @return Group
     * @throws PermissionDeniedException
     */
    public function handle(EditGroup $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $group = $this->groups->findOrFail($command->groupId, $actor);

        $this->assertCan($actor, 'edit', $group);

        $attributes = array_get($data, 'attributes', []);

        if (isset($attributes['nameSingular']) && isset($attributes['namePlural'])) {
            $group->rename($attributes['nameSingular'], $attributes['namePlural']);
        }

        if (isset($attributes['color'])) {
            $group->color = $attributes['color'];
        }

        if (isset($attributes['icon'])) {
            $group->icon = $attributes['icon'];
        }

        $this->events->fire(
            new GroupWillBeSaved($group, $actor, $data)
        );

        $group->save();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}