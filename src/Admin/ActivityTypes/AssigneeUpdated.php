<?php

namespace Igniter\Admin\ActivityTypes;

use Igniter\Admin\Helpers\ActivityMessage;
use Igniter\Admin\Models\AssignableLog;
use Igniter\Flame\ActivityLog\Contracts\ActivityInterface;
use Igniter\Flame\ActivityLog\Models\Activity;
use Igniter\Flame\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;

class AssigneeUpdated implements ActivityInterface
{
    public const ORDER_ASSIGNED_TYPE = 'orderAssigned';

    public const RESERVATION_ASSIGNED_TYPE = 'reservationAssigned';

    public $type;

    public $subject;

    public $causer;

    public function __construct(string $type, Model $subject, User $causer = null)
    {
        $this->type = $type;
        $this->subject = $subject;
        $this->causer = $causer;
    }

    /**
     * @param \Igniter\Admin\Models\AssignableLog $assignableLog
     * @param \Igniter\Flame\Auth\Models\User|null $user
     */
    public static function log(AssignableLog $assignableLog, User $user = null)
    {
        $type = $assignableLog->isForOrder() ? self::ORDER_ASSIGNED_TYPE : self::RESERVATION_ASSIGNED_TYPE;

        $recipients = [];
        foreach ($assignableLog->assignable->listGroupAssignees() as $assignee) {
            if ($user && $assignee->getKey() === $user->getKey()) continue;
            $recipients[] = $assignee;
        }

        activity()->logActivity(new self($type, $assignableLog->assignable, $user), $recipients);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getCauser()
    {
        return $this->causer;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        $keyName = $this->type == self::ORDER_ASSIGNED_TYPE ? 'order_id' : 'reservation_id';

        return [
            $keyName => $this->subject->getKey(),
            'assignee_id' => $this->subject->assignee_id,
            'assignee_name' => optional($this->subject->assignee)->staff_name,
            'assignee_group_id' => $this->subject->assignee_group_id,
            'assignee_group_name' => optional($this->subject->assignee_group)->user_group_name,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getTitle(Activity $activity)
    {
        return lang($activity->type == self::ORDER_ASSIGNED_TYPE
            ? 'igniter::admin.orders.activity_event_log_assigned_title'
            : 'igniter::admin.reservations.activity_event_log_assigned_title');
    }

    /**
     * {@inheritdoc}
     */
    public static function getUrl(Activity $activity)
    {
        $url = $activity->type == self::ORDER_ASSIGNED_TYPE ? 'orders' : 'reservations';
        if ($activity->subject)
            $url .= '/edit/'.$activity->subject->getKey();

        return admin_url($url);
    }

    /**
     * {@inheritdoc}
     */
    public static function getMessage(Activity $activity)
    {
        $lang = $activity->type == self::ORDER_ASSIGNED_TYPE
            ? 'igniter::admin.orders.activity_event_log_assigned'
            : 'igniter::admin.reservations.activity_event_log_assigned';

        return ActivityMessage::attachAssignedPlaceholders($lang, $activity);
    }
}
