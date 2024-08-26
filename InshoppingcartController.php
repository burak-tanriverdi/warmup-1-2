<?php

namespace App\Http\Controllers\API\Partners;

use App\Http\Controllers\Controller;
use App\Traits\Message\ResponseMessage;
use Illuminate\Http\Request;
use App\Services\API\Partners\Inshoppingcart\UpsertUserDataService;
use App\Services\API\Partners\Inshoppingcart\DataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;

/**
 * Class InshoppingcartController
 * @package App\Http\Controllers\API\Partners
 */
class InshoppingcartController extends Controller
{
    use ResponseMessage;

    /**
     * @param Request $request
     * @param UpsertUserDataService $upsertUserDataService
     * @return object
     */
    public function upsertUserData(Request $request, UpsertUserDataService $upsertUserDataService): object
    {
        return $upsertUserDataService->upsert($request->route('partnerName'), (object)$request->all());
    }

    /**
     * @param Request $request
     * @param DataService $dataService
     * @return JsonResponse
     */
    public function readData(Request $request, DataService $dataService): JsonResponse
    {
        return response()->json(
            $dataService->readData($request->route('partnerName'), $request->header('limit', 5))
        );
    }

    /**
     * @param Request $request
     * @param DataService $dataService
     * @return JsonResponse
     */
    public function writeData(Request $request, DataService $dataService): JsonResponse
    {
        return response()->json(
            $dataService->writeData($request->route('partnerName'), (object)$request->all())
        );
    }

    /**
     * @param Request $request
     * @param UpsertUserDataService $upsertUserDataService
     * @return object
     */
    public function getUserProfile(Request $request, UpsertUserDataService $upsertUserDataService): object
    {
        return $upsertUserDataService->getUserProfile($request->route('partnerName'), $request->query('pn'));
    }

    /**
     * @param DataService $dataService
     * @return JsonResponse
     */
    public function getPartner(Request $request, DataService $dataService): JsonResponse
    {
        return response()->json($dataService->getPartnersById());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function queue(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'request' => $request->all(),
        ]);
    }

    /**
     * @param Request $request
     * @param DataService $dataService
     * @return JsonResponse
     */
    public function sendRequest(Request $request, DataService $dataService): JsonResponse
    {
        return response()->json($dataService->sendRequest((object)$request->all()));
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEmailInformation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid email.'], 400);
        }

        $email = $request->query('email');

        $count = Redis::get("email_request_count:{$email}");

        $count = $count ? (int) $count : 0;

        Redis::incr("email_request_count:{$email}");

        return response()->json(['email' => $email, 'count' => $count + 1]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserAttributes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'insider_id' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input.'], 400);
        }
    
        $email = $request->input('email');
        $insiderId = $request->input('insider_id');
    
        if ($email && $insiderId) {
            return response()->json(['message' => 'Just choose an identifier.'], 400);
        }
    
        if (!$email && !$insiderId) {
            return response()->json(['message' => 'Provide either an email or an insider_id.'], 400);
        }
    
        $responseData = $this->simulateUCDService($email, $insiderId);
    
        if (isset($responseData['error']) && in_array($responseData['error'], ['does not exist', 'no-data'])) {
            return response()->json(['message' => "No such user for these identifiers: no data."], 404);
        }
    
        return response()->json(['attributes' => $responseData['attributes'] ?? []]);
    }
    
    private function simulateUCDService($email, $insiderId)
    {
        $mockDatabase = [
            'test@test.com' => ['name' => 'John Doe', 'age' => 30],
            '0b215674-070e-41a5-acd7-5273b67237ff' => ['name' => 'Jane Smith', 'age' => 25],
        ];
    
        $identifier = $email ?: $insiderId;
    
        if (isset($mockDatabase[$identifier])) {
            return ['attributes' => $mockDatabase[$identifier]];
        }
    
        return ['error' => 'no-data'];
    }
    
}
