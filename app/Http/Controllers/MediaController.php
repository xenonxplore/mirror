<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Event;
use App\Album;
use App\PhotoAlbum;
use App\Video;
use Carbon\Carbon;

class MediaController extends Controller
{
    public function index ()
    {
        $now = new Carbon;
        $albums = PhotoAlbum::orderBy('created_at', 'desc')->paginate(24);
        $videos = Video::orderBy('created_at', 'desc')->paginate(9);

        return view('media/index')->with('albums', $albums)->with('videos', $videos);
    }

    public function addPhoto ()
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this')->error();

            return redirect('/media');
        }

        return redirect('/media/photo/edit');

        // $es = Event::orderBy('created_at', 'desc')->get();
        // $events = array();

        // foreach ($es as $e) {
        //     if (count($e->album) == 0) {
        //         array_push($events, $e);
        //     }
        // }

        // return view('media/photo-add')->with('events', $events);
    }

    public function storePhoto (Request $request)
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this view')->error();

            return redirect('/media');
        }

        $this->validate($request, [
            'event' => 'required',
            'url.*' => 'required|image|max:3000',
            'caption.*' => 'required|max:30',
        ]);

        $counter = 0;

        foreach ($request->url as $url) {
            $album = new Album;
            $album->url = $this->uploadImage($url);
            $album->caption = $request->caption[$counter];
            $album->event_id = $request->event;
            $counter++;
            $album->save();
        }

        flash('Images have been successfully stored')->success();

        return redirect('/media');
    }

    public function showAlbums ()
    {
        $events = Event::orderBy('date_end', 'desc')->get();

        return view('media/show-albums')->with('events', $events);
    }

    public function editAlbum (Request $request)
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this view')->error();

            return redirect('/media');
        }

        if ($request->album == null) {
            return redirect('/album/create');

            // return view('media/event-select')->with('events', PhotoAlbum::all())->with('url', '/media/photo/edit');
        } else {
            $album = null;

            if ($request->album != 0) {
                $album = PhotoAlbum::find($request->album);
            }

            return view('media/edit-album')->with('event', $album);
        }
    }

    public function updateAlbum (Request $request)
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this view')->error();

            return redirect('/media');
        }

        $this->validate($request, [
            'caption.*' => 'required|max:30',
            'name' => 'required|max:30'
        ]);

        if ($request->cap != null) {
            $this->validate($request, [
                'all' => 'required|image|max:3000',
            ]);
        } elseif ($request->hasfile('all')) {
            $this->validate($request, [
                'cap' => 'required|max:30',
            ]);
        }

        $url = '/media/photo/edit?album='.$request->id;
        $counter = 0;

        if ($request->id == 0) {
            $albums = Album::where('event_id', 0)->get();

            foreach ($albums as $album) {
                $album->caption = $request->caption[$counter];
                $album->save();
    
                $counter++;
            }
    
            if ($request->hasfile('all')) {
                $album = new Album;
                $album->url = $this->updateImage($request->all, $album->url);
                $album->event_id = 0;
                $album->caption = $request->cap;
    
                $album->save();
            }

            $photoAlbum = PhotoAlbum::find(0);
            $photoAlbum->name = $request->name;
            $photoAlbum->featured_id = $request->feature_id;
            $photoAlbum->save();

            flash('Updates successfully made')->success();

            return redirect ($url."0");
        } else {
            $albums = PhotoAlbum::find($request->id);

            // dd($event, $id);

            foreach ($albums->photos as $album) {
                $album->caption = $request->caption[$counter];
                $album->save();
    
                $counter++;
            }
    
            if ($request->hasfile('all')) {
                $album = new Album;
                $album->url = $this->updateImage($request->all, $album->url);
                $album->event_id = $request->id;
                $album->caption = $request->cap;
    
                $album->save();
            }

            $photoAlbum = PhotoAlbum::find($request->id);
            $photoAlbum->featured_id = $request->feature_id;
            $photoAlbum->name = $request->name;
            $photoAlbum->save();
        }

        

        flash('Updates successfully made')->success();

        return redirect ($url);
    }

    public function deletePhoto (Request $request, $id)
    {
        $album = Album::find($id);
        $url = '/media/photo/edit?album='.$album->event_id;

        if ($album->event_id == 0) {
            $url = '/media/photo/edit?album=0';
        }

        $album->delete();

        flash('Photo successfully deleted');

        return redirect($url);
    }

    public function addVideo ()
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this')->error();

            return redirect('/media');
        }

        $events = Event::orderBy('created_at', 'desc')->get();

        return view('media/video-add')->with('events', $events);
    }

    public function storeVideo (Request $request)
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this')->error();

            return redirect('/media');
        }

        $this->validate($request, [
            'event' => 'required',
            'url' => 'required',
        ]);

        $video = new Video;
        $video->event_id = $request->event;
        $video->featured = true;

        try {
            $video->url = $this->findSRC($request->url);
        }
        catch (\Exception $e) {
            flash('Please enter an embedded link')->error();

            return redirect ('/media/video/add');
        }

        $video->timestamps = false;
        $video->save();

        $url = '/media/video/edit?='.$request->event;

        flash('Video successfully added')->success();

        return redirect($url);
    }

    public function editVideo (Request $request)
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this view')->error;
        }

        if ($request->event == null) {
            return view('media/event-select')->with('events', Event::all())->with('url', '/media/video/edit');
        } else {
            $event = null;

            if ($request->event != 0) {
                $event = Event::find($request->event);
            }

            return view('media/edit-video')->with('event', $event);
        }
    }

    public function updateVideo (Request $request)
    {
        if ($this->notAdmin()) {
            flash('You are not authorized to access this view')->error;
        }

        $this->validate($request, [
            'url.*' => 'required',
        ]);

        $rdurl;

        if ($request->id == 0) {
            $rdurl = '/media/video/edit?event='.$request->id;
        } else {
            $rdurl = "/media/video/edit?event="."0";
        }

        if ($request->id != 0) {
            $event = Event::find($request->id);
            $counter = 0;

            foreach ($event->videos as $video) {
                $url = explode(' ', $request->url[$counter]);
                $i = 0;
                $flag = false;

                for ($i = 0; $i < sizeof($url); $i++) {
                    if (strpos($url[$i], 'src') !== false) {
                        break;
                        $flag = true;
                    }
                }

                if ($flag) {

                    try {
                        $video->url = $this->findSRC($request->url[$counter]);
                    }
                    catch (\Exception $e) {
                        flash('Please enter an embedded link')->error();
            
                        return redirect ($rdurl);
                    }

                    $video->timestamps = false;
                    $video->save();
                }

                $counter++;
            }

            if ($request->new != null) {
                $video = new Video;

                try {
                    $video->url = $this->findSRC($request->new);
                }
                catch (\Exception $e) {
                    flash('Please enter an embedded link')->error();
        
                    return redirect ($rdurl);
                }

                $video->event_id = $request->id;
                $video->featured = false;
                $video->timestamps = false;
                $video->save();
            }
        } else {
            $videos = Video::where('event_id', 0)->get();
            $counter = 0;
            
            foreach ($videos as $video) {
                $url = explode(' ', $request->url[$counter]);
                $i = 0;
                $flag = false;

                for ($i = 0; $i < sizeof($url); $i++) {
                    if (strpos($url[$i], 'src') !== false) {
                        break;
                        $flag = true;
                    }
                }

                if ($flag) {

                    try {
                        $video->url = $this->findSRC($request->url[$counter]);
                    }
                    catch (\Exception $e) {
                        flash('Please enter an embedded link')->error();
            
                        return redirect ($rdurl."0");
                    }

                    $video->timestamps = false;
                    $video->save();
                }

                $counter++;
            }

            if ($request->new != null) {
                $video = new Video;

                try {
                    $video->url = $this->findSRC($request->new);
                }
                catch (\Exception $e) {
                    flash('Please enter an embedded link')->error();
        
                    return redirect ($rdurl."0");
                }

                $video->event_id = 0;
                $video->featured = false;
                $video->timestamps = false;
                $video->save();
            }
        }

        flash('Updates successfully made')->success();

        if ($request->id == 0) {
            return redirect ($rdurl."0");
        } else {
            return redirect ($rdurl);
        }
    }

    public function deleteVideo (Request $request, $id)
    {
        $video = Video::find($id);
        $url = '/media/video/edit?event='.$video->event_id;
        $video->delete();

        flash('Video successfully deleted');

        return redirect($url);
    }
}
