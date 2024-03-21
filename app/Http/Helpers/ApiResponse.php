<?php namespace App\Http\Helpers;

use Symfony\Component\HttpFoundation\Response as ResponseCode;

class ApiResponse {

    /**
     * @var array
     */
    private $data;

    /**
     * @var boolean
     */
    private $status;

    /**
     * @var int
     */
    private $responseCode;

    /**
     * @var string
     */
    private $message;

    private function __construct($data, $status, $responseCode, $message)
    {
        $this->data = $data;
        $this->status = $status;
        $this->responseCode = $responseCode;
        $this->responseCode = $message ? : ResponseCode::$statusTexts[$responseCode];
    }

    /**
     * setup, build and return the response.
     *
     * @param array  $data An array of returned data
     * @param int    $status true or false for the operation status
     * @param int    $responseCode the Http response code
     * @param string $message response message
     *
     * @return \Illuminate\Http\Response
     */
    public static function send($data, $status = true, $responseCode = ResponseCode::HTTP_OK, string $message = "")
    {
        return (new static($data, $status, $responseCode, $message))->buildResponse();
    }

    /**
     * build the response.
     *
     * @return \Illuminate\Http\Response
     */
    private function buildResponse()
    {
        return response()->json([
            'base_url'=> \URL::to('/'),
            'status'  => (boolean) $this->status,
            'message' => $this->message,
            'data'    => $this->data,
        ], $this->responseCode);
    }
}