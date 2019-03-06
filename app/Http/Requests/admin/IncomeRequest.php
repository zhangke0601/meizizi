<?php

namespace App\Http\Requests\admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncomeRequest extends FormRequest
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
            'month' => [
                'required',
                 Rule::unique('pfdatas')->where(function ($query) {
                    $query->where('cartoon_id', '=', $this->cartoon_id);
                 })
            ]
        ];
    }

    public function messages()
    {
        return [
            'month.required' => '月份不能为空',
            'month.unique' => '数据已经存在，请点击对应月份和作品批量编辑',
        ];
    }
}
