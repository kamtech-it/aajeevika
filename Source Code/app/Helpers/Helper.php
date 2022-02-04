<?php
namespace App\Helpers;

use App\User;
use App\ProductMaster;
use App\Category;
use App\CertificateType;

use DB;
use Illuminate\Support\Facades\Mail;
class Helper
{

	public static function getTotalActiveProduct($sellerId){
		try{
				$count =ProductMaster::where('is_active',1)->where('user_id',$sellerId)->count();
				return $count;
		}catch(Exception $e){
			echo 'Caught exception: '. $e->getMessage() ."\n";
		}
	}
	
    public static function getCatBySubCat($parentId){
		try{
				$cat =Category::where('is_active',1)->where('parent_id',$parentId)->first();
				return $cat;
		}catch(Exception $e){
			echo 'Caught exception: '. $e->getMessage() ."\n";
		}
	}
	public static function getCertificateTypeById($typeId, $language)
	{
		# code...
		try{
			$name = 'name_en  as name';
			if ($language == 'hi') {
				$name = 'name_hi  as name';
			}
			$type =CertificateType::select('id',$name)->where('id',$typeId)->first();
			return $type;
		}catch(Exception $e){
			echo 'Caught exception: '. $e->getMessage() ."\n";
		}
	}
	
	
}