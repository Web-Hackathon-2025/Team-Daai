<?php

namespace App\Traits;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
trait ResponseAPI
{
    /**
     * Core of response
     *
     * @param   string          $message
     * @param   array|object    $data
     * @param   integer         $statusCode of App
     * @param   boolean         $isSuccess
     * @param   integer         $httpResponseCode
     */
    public function coreResponse($message, $statusCode, $isSuccess = true, $httpResponseCode = 200, $data = null)
    {
        if (!$message) {
            return response()->json(['message' => 'Message is required'], 500);
        }

        if ($isSuccess) {
            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data,
            ], $httpResponseCode);
        }

        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'code'    => $statusCode,
        ], $httpResponseCode);
    }

    /**
    * Send validaton response
    * @param   string  $message
    */
    public function validationResponse($message)
    {
        return $this->coreResponse($message, 422, false, 422);
    }

    public function validationMessage(){
        return [
            'unique' => 'The :attribute already taken.',
            'required' => 'The :attribute field is required.',
            'max:32' => ':attribute less then 32 chars.',
            'confirmed' => 'New and confirm :attribute no equal.',
            'max:255' => ':attribute less then 255 chars.',
            'string' => ':attribute not a string',
            'max:191' => ':attribute less then 191 chars.',
        ];
    }
    /**
    * Send not authorized response
    * @param   string  $message
    */
    public function notAuthorizedResponse($message)
    {
        return $this->coreResponse($message,401,false,200);
    }

    /**
     * Send any success response
     *
     * @param   array|object    $data
     * @param   string          $message
     * @param   integer         $statusCode
     */
    public function success($message, $data = null, $statusCode = 200)
    {
        return $this->coreResponse($message ?? "Data Fetched", $statusCode, true, $statusCode, $data);
    }

    /**
     * Send any error response
     *
     * @param   string          $message
     * @param   integer         $statusCode
     */
    public function error($message, $statusCode = 400)
    {
        return $this->coreResponse($message, $statusCode, false, $statusCode);
    }

    function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => convertErrorArrayToString($validator->errors()->getMessages()),
            'code'    => 422,
        ], 422));
    }
}
