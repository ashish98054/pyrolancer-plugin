<?php namespace Ahoy\Pyrolancer\Models;

use Auth;
use Model;
use ApplicationException;
use Ahoy\Pyrolancer\Models\Attribute;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Markdown;

/**
 * ProjectBid Model
 */
class ProjectBid extends Model
{

    use \Ahoy\Traits\ModelUtils;
    use \October\Rain\Database\Traits\Validation;

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_HIDDEN = 'hidden';
    const STATUS_SHORTLISTED = 'shortlisted';
    const STATUS_ACCEPTED = 'accepted';

    const TYPE_FIXED = 'fixed';
    const TYPE_HOURLY = 'hourly';

    /*
     * Validation
     */
    public $rules = [
        'details' => 'required',
        'type' => 'required',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'ahoy_pyrolancer_project_bids';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'details',
        'hourly_rate',
        'hourly_hours',
        'fixed_rate',
        'fixed_days',
        'type'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user'    => ['RainLab\User\Models\User'],
        'worker'  => ['Ahoy\Pyrolancer\Models\Worker'],
        'project' => ['Ahoy\Pyrolancer\Models\Project'],
        'status'  => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'bid.status'"],
        'type'    => ['Ahoy\Pyrolancer\Models\Attribute', 'conditions' => "type = 'bid.type'"],
    ];

    public function beforeSave()
    {
        if ($this->isDirty('details'))
            $this->details_html = Markdown::parse(trim($this->details));
    }

    public function beforeCreate()
    {
        if (!$this->status_id) {
            $this->status = Attribute::forType(Attribute::BID_STATUS)
                ->whereCode('active')
                ->first();
        }
    }

    public function beforeValidate()
    {
        if ($this->type == self::TYPE_FIXED) {
            $this->hourly_rate = 0;
            $this->hourly_hours = null;
            $this->rules['fixed_rate'] = 'required';
            $this->rules['fixed_days'] = 'required';
        }
        else {
            $this->fixed_rate = 0;
            $this->fixed_days = null;
            $this->rules['hourly_rate'] = 'required';
            $this->rules['hourly_hours'] = 'required';
        }
    }

    public static function makeForProject($project, $user = null)
    {
        if ($user === null)
            $user = Auth::getUser();

        if (!$user)
            throw new ApplicationException('You must be logged in!');

        $worker = WorkerModel::getFromUser($user);

        $bid = new static;
        $bid->user = $user;
        $bid->project = $project;
        $bid->worker = $worker;
        return $bid;
    }

}