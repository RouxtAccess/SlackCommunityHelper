<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EventsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'token' => 'required|string',
            'team_id' => 'required|string',
            'type' => 'required|string',
            'api_app_id' => 'required|string',
            'event' => 'required',
            'event_id' => 'required|string',
            'event_time' => 'required',
        ];
    }
}
