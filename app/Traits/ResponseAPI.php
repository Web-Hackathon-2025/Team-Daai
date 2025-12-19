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
    public function coreResponse($message, $statusCode, $isSuccess = true,$httpResponseCode=200,$data = null)
    {
        // Check the param
        if(!$message) return response()->json(['message' => 'Message is required'], 500);

        // Send the response
        if($isSuccess)
        {
            return response()->json([
                'Message'   => $message,
                'Success'   => true,
                'Data'      => $data,
                "Status" => 200
            ], $httpResponseCode);
        }
        else
        {
            return response()->json([
                'Message'   => $message,
                'Success'   => $isSuccess,
                'Status'    => $statusCode,
            ], $httpResponseCode);
        }
    }

    /**
    * Send validaton response
    * @param   string  $message
    */
    public function validationResponse($message)
    {
        return $this->coreResponse($message,422,false,200);
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
    public function success($data=null,$message,$statusCode = 200)
    {
        return $this->coreResponse($message ?? "Data Fetched",$statusCode,true,200,$data);
    }

    /**
     * Send any error response
     *
     * @param   string          $message
     * @param   integer         $statusCode
     */
    public function error($message, $statusCode = 200)
    {
        return $this->coreResponse($message,$statusCode,false, $statusCode);
    }

    function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'Message'   => convertErrorArrayToString($validator->errors()->getMessages()),
            'Success'   => false,
            'Status'    => 200,
        ],200));
    }
}
