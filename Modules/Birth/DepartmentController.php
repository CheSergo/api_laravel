<?php
namespace App\Modules\Birth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Helpers
use Carbon\Carbon;
use App\Helpers\HString;

// Models
use App\Modules\Departments\Department;
use App\Jobs\DepartmentBuilder;
use App\Modules\Birth\ConfigController;

use Exception;

class DepartmentController extends Controller {
    public function departments(Request $request) {
        DepartmentBuilder::dispatch($request->site_id, $request->type, $request->config);
    }


    function createDepartment($department, $parent_id, $site_id, $type_id) {

        $model = Department::create([
            'title' => $department->title,
            'slug' => strtolower(HString::transliterate($department->title)),
            'sort' => isset($department->sort) && !is_null($department->sort) ? $department->sort : 100,
            'parent_id' => $parent_id,
            'credentials' => isset($department->credentials) && !is_null($department->credentials) ? $department->credentials : null,
            'servicies' => isset($department->servicies) && !is_null($department->servicies) ? $department->servicies : null,
            'redirect' => isset($department->redirect) && !is_null($department->redirect) ? $department->redirect : null,
            'phone' => isset($department->phone) && !is_null($department->phone) ? $department->phone : null,
            'email' => isset($department->email) && !is_null($department->email) ? $department->email : null,
            'fax' => isset($department->fax) && !is_null($department->fax) ? $department->fax : null,
            'address' => isset($department->address) && !is_null($department->address) ? $department->address : null,
            'type_id' => $type_id,
            'creator_id' => 0,
            'editor_id' => 0,
            'site_id' => $site_id,
            'is_published' => true,
            'created_at' => Carbon::now(),
            'published_at' => Carbon::now(),
        ]);

        if(isset($department->children) && count($department->children)) {
            foreach($department->children as $child) {
                $tmp = (array) $child;
                if(!empty($tmp)) {
                    $this->createDepartment($child, $model->id, $site_id, $model->type->id);
                }
            }
        }
    }

    public function giveBirthToDepartments($site_id, $type, $config) {

        $configController = new ConfigController();
        $config = $configController->getConfig($type, $config);
        foreach($config as $department) {
            $tmp = (array) $department;
            if(!empty($tmp)) {
                $this->createDepartment($department, null, $site_id, $department->type_id);
                continue;
            }
        }
        return 'success';

    }
}