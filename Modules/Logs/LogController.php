<?php

namespace App\Modules\Logs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

use Spatie\Activitylog\Models\Activity;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

use App\Helpers\Meta;
use App\Helpers\Api\ApiResponse;

use App\Models\User;
use App\Modules\Logs\LogFilter;

class LogController extends Controller
{
    public $messages = [
        'show' => 'Просмотр лога',
        'not_found' => 'Элемент не найден',
    ];

    public function index(Request $request) {
        $builder = Activity::orderBy('updated_at', 'desc')->orderBy('created_at', 'desc');

        $logFilter = new LogFilter();
        foreach ($request->all() as $key => $value) {
            $methodName = $key;
            if (method_exists($logFilter, $methodName)) {
                if ($methodName == 'title') {
                    if (isset($request->model) && !is_null($request->model)) {
                        $builder = $logFilter->$methodName($builder, $value, $request->model);
                    }
                } else {
                    $builder = $logFilter->$methodName($builder, $value);
                }
            }
        }
        $logs = $builder->get();
        $items = collect();

        foreach ($logs as $log) {
            $collect_log = collect([
                'id'            => $log->id,
                'created_at'    => $log->created_at,
                'class'         => class_basename($log->subject),
                'model'         => $log->subject,
                'user'          => $log->causer,
                'method'        => $log->description,
                'changes'       => $log->properties]);
            $items->push($collect_log);
        }

        $currentPage = $request->input('page', 1);
        $perPage = 10;
        $offset = ($currentPage * $perPage) - $perPage;

        $paginator = new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            count($items),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $paginator = $paginator->jsonSerialize();

        $meta = [
            'path' => $paginator['path'],
            'pagination'        => [
                'to'            => $paginator['to'],
                'from'          => $paginator['from'],
                'total'         => $paginator['total'],
                'current_page'  => $paginator['current_page'],
                'per_page'      => $paginator['per_page'],
                'last_page'     => $paginator['last_page'],
            ]
        ];



        return [
            'meta' => $meta,
            'data' => $paginator['data'],
        ];
    }

    public function show($id) {
        if($item = Activity::find($id)) {
            $author = "";
            if (isset($item->subject) && !empty($item->subject->creator_id)) {
                $user = User::find($item->subject->creator_id);
                if (!empty($user)) {
                    $second_name = $user->second_name ? ' ' . $user->second_name : '';
                    $author = $user->surname . ' ' . $user->name . $second_name . ' (' . $user->email . ')';
                }
            }

            $data = [
                'id'        => $item->id,
                'created_at'=> $item->created_at,
                'class'     => class_basename($item->subject),
                'method'    => $item->description,
                'model'     => $item->subject,
                'user'      => $item->causer,
                'changes'   => $item->properties,
                'author'    => $author
            ];
            return ApiResponse::onSuccess(200, $this->messages['show'], $data);
        } else {
            return ApiResponse::onError(404, $this->messages['not_found'], $errors = ['id' => 'not Found',]);
        };
    }

//    public function showLogs()
//    {
//        $path = storage_path('logs/laravel.log');
//
//        if (!file_exists($path)) {
//            return response()->json(['message' => 'No log file found.']);
//        }
//
//        $lines = file($path);
//
//        return response()->json($lines);
//    }
}
