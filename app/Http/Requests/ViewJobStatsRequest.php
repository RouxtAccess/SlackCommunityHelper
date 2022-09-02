<?php

namespace App\Http\Requests;

use App\Services\SlackService\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ViewJobStatsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if(Auth::user()?->isSuperAdmin())
        {
            return true;
        }
        if($this->job->type === Job::TYPE_PRO && $this->job->workspace->paidTierStatsEnabled && (auth()->user()?->isWorkspaceAdmin() || auth()->user()?->getKey() === $this->job->user_id))
        {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
