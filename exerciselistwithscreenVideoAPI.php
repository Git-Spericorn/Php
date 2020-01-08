 public function exercise($id){
      $video=Video::orderBy('id','DESC')->first();
      $minutes=Time::all();
      $bands=Band::where('isDelete','=',0)->orderBy('priority_order','ASC')->get();
      $exerciseUnsaved= !Session::get('activity_values')  ? [] : Session::get('activity_values');
      // get video id
      $videoThumb='';
      $videoUrl='';
      if($video){
        $videoUrl=$video->url;
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video->url, $match)) {
          $video_id = $match[1];
        }
        if($video_id){
          $videoThumb='https://img.youtube.com/vi/'.$video_id.'/0.jpg';
        }
      }
      // get video id

      $today_date=Carbon::now();
      $weekStartDate=$today_date->startOfWeek()->subDays(1)->format('Y-m-d');
      $today_date=Carbon::now();
      $weekEndDate=$today_date->endOfWeek()->subDays(1)->format('Y-m-d');

      $exercises=StretchClassExercise::with(['exercise'=>function($query){

        $query->with('tutorial','sub_categories');
       }])
       ->where('class_id','=',$id)
       ->get()
       ->sortBy('priority_order');
      //  return $exercises;
       $weeklyclass=StretchClass::find($id);

        return view('web.exercise')->with(['exercises'=>$exercises,
        'minutes'=>$minutes,'video'=>$videoUrl,'videoThumb'=>$videoThumb,'bands'=>$bands,'exerciseUnsaved'=>$exerciseUnsaved,"id"=>$id,"weeklyclass"=>$weeklyclass]);
    }
