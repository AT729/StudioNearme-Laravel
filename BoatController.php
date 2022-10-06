<?php
namespace Modules\Boat\Controllers;

use App\Http\Controllers\Controller;
use Modules\Boat\Models\Boat;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;
use Modules\Review\Models\Review;
use Modules\Core\Models\Attributes;
use DB;

class BoatController extends Controller
{
    protected $boatClass;
    protected $locationClass;
    public function __construct()
    {
        $this->boatClass = Boat::class;
        $this->locationClass = Location::class;
    }

    public function callAction($method, $parameters)
    {
        if(!Boat::isEnable())
        {
            return redirect('/');
        }
        return parent::callAction($method, $parameters); // TODO: Change the autogenerated stub
    }
    public function index(Request $request)
    {

        $is_ajax = $request->query('_ajax');
        $list = call_user_func([$this->boatClass,'search'],$request);
        $markers = [];
        if (!empty($list)) {
            foreach ($list as $row) {
                $markers[] = [
                    "id"      => $row->id,
                    "title"   => $row->title,
                    "lat"     => (float)$row->map_lat,
                    "lng"     => (float)$row->map_lng,
                    "gallery" => $row->getGallery(true),
                    "infobox" => view('Boat::frontend.layouts.search.loop-grid', ['row' => $row,'disable_lazyload'=>1,'wrap_class'=>'infobox-item'])->render(),
                    'marker' => get_file_url(setting_item("boat_icon_marker_map"),'full') ?? url('images/icons/png/pin.png'),
                ];
            }
        }
        $limit_location = 15;
        if( empty(setting_item("boat_location_search_style")) or setting_item("boat_location_search_style") == "normal" ){
            $limit_location = 1000;
        }
        $data = [
            'rows'               => $list,
            'list_location'      => $this->locationClass::where('status', 'publish')->limit($limit_location)->with(['translations'])->get()->toTree(),
            'boat_min_max_price' => $this->boatClass::getMinMaxPrice(),
            'markers'            => $markers,
            "blank" => setting_item('search_open_tab') == "current_tab" ? 0 : 1 ,
            "seo_meta"           => $this->boatClass::getSeoMetaForPageList()
        ];
        $layout = setting_item("boat_layout_search", 'normal');
        if ($request->query('_layout')) {
            $layout = $request->query('_layout');
        }
        if ($is_ajax) {
            return $this->sendSuccess([
                'html'    => view('Boat::frontend.layouts.search-map.list-item', $data)->render(),
                "markers" => $data['markers']
            ]);
        }
        $data['attributes'] = Attributes::where('service', 'boat')->orderBy("position","desc")->with(['terms','translations'])->get();

        if ($layout == "map") {
            $data['body_class'] = 'has-search-map';
            $data['html_class'] = 'full-page';
            return view('Boat::frontend.search-map', $data);
        }
        return view('Boat::frontend.search', $data);
    }

    public function detail(Request $request, $slug)
    {
        $row = $this->boatClass::where('slug', $slug)->with(['location','translations','hasWishList'])->first();;
        if ( empty($row) or !$row->hasPermissionDetailView()) {
            return redirect('/');
        }
        $translation = $row->translateOrOrigin(app()->getLocale());
        $boat_related = [];
        $location_id = $row->location_id;
        if (!empty($location_id)) {
            $boat_related = $this->boatClass::where('location_id', $location_id)->where("status", "publish")->take(4)->whereNotIn('id', [$row->id])->with(['location','translations','hasWishList'])->get();
        }
        $review_list = $row->getReviewList();
        $data = [
            'row'          => $row,
            'translation'       => $translation,
            'boat_related' => $boat_related,
            'booking_data' => $row->getBookingData(),
            'review_list'  => $review_list,
            'seo_meta'  => $row->getSeoMetaWithTranslation(app()->getLocale(),$translation),
            'body_class'=>'is_single'
        ];
        $this->setActiveMenu($row);
        return view('Boat::frontend.detail', $data);
    }
    public function detailFromId($booking_id=null,$boat_id=null)
    {
        $row = $this->boatClass::where('id', $boat_id)->with(['location','translations','hasWishList'])->first();;
        if ( empty($row) or !$row->hasPermissionDetailView()) {
            return redirect('/');
        }
        $translation = $row->translateOrOrigin(app()->getLocale());
        $boat_related = [];
        $location_id = $row->location_id;
        if (!empty($location_id)) {
            $boat_related = $this->boatClass::where('location_id', $location_id)->where("status", "publish")->take(4)->whereNotIn('id', [$row->id])->with(['location','translations','hasWishList'])->get();
        }
        $review_list = $row->getReviewList();
        $data = [
            'row'          => $row,
            'translation'       => $translation,
            'boat_related' => $boat_related,
            'booking_data' => $row->getBookingData(),
            'review_list'  => $review_list,
            'seo_meta'  => $row->getSeoMetaWithTranslation(app()->getLocale(),$translation),
            'body_class'=>'is_single',
            'update_booking_date' => $booking_id
        ];
        //set session for updating booking date
        session()->put('update_booking_date',$booking_id);
        $this->setActiveMenu($row);
        return view('Boat::frontend.detail', $data);
    }
}
