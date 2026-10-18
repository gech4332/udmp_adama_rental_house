<?php
/* Ethiopian regions and the cities/towns within each one.
   Used by the post/edit forms (cascading Region -> City selects) and by the
   index.php search filters. */

function ethio_regions() {
    return [
        'Addis Ababa' => [
            'Addis Ababa', 'Addis Ketema', 'Akaki Kality', 'Arada', 'Bole',
            'Gulele', 'Kirkos', 'Kolfe Keranio', 'Lideta', 'Nifas Silk-Lafto',
            'Yeka', 'Lemi Kura',
        ],
        'Afar' => [
            'Semera', 'Asaita', 'Dubti', 'Logiya', 'Mille', 'Awash', 'Gewane',
            'Chifra', 'Abala', 'Berhale', 'Dallol', 'Afdera', 'Erebti', 'Koneba',
            'Megale', 'Amibara', 'Bidu', 'Elidar', 'Kori', 'Teru', 'Yalo',
            'Golina', 'Dewe', 'Telalak', 'Tuli Guled',
        ],
        'Amhara' => [
            'Bahir Dar', 'Gondar', 'Dessie', 'Kombolcha', 'Debre Birhan',
            'Debre Markos', 'Debre Tabor', 'Woldia', 'Finote Selam', 'Bure',
            'Injibara', 'Mota', 'Bichena', 'Dejen', 'Kemise', 'Wereta',
            'Addis Zemen', 'Kobo', 'Sekota', 'Lalibela', 'Mehal Meda',
            'Shewa Robit', 'Bati', 'Debark', 'Metema', 'Dangila', 'Chagni',
            'Merawi', 'Woreta', 'Gorgora',
        ],
        'Benishangul-Gumuz' => [
            'Assosa', 'Bambasi', 'Gilgel Beles', 'Kurmuk', 'Menge', 'Sherkole',
            'Homosha', 'Kamashi', 'Sirba Abay', 'Yaso', 'Bulen', 'Dangur',
            'Dibate', 'Guba', 'Mandura', 'Wenbera', 'Pawe',
        ],
        'Central Ethiopia' => [
            'Hosaena', 'Durame', 'Butajira', 'Wolkite', 'Worabe', 'Alaba Kulito',
            'Areka', 'Boditi', 'Shone', 'Kembata', 'Silti', 'Wachemo',
        ],
        'Dire Dawa' => [
            'Dire Dawa', 'Dechatu', 'Melka Jebdu', 'Biyo Awale', 'Ganda Gara',
            'Aseliso',
        ],
        'Gambella' => [
            'Gambella', 'Abobo', 'Itang', 'Godere', 'Mengesh', 'Gog', 'Jore',
            'Akobo', 'Jikawo', 'Lare', 'Wantawo', 'Dima',
        ],
        'Harari' => [
            'Harar', 'Hundene', 'Aw Abdal', 'Aw Umer', 'Gey Sinan', 'Sofi',
            'Jugol',
        ],
        'Oromia' => [
            'Adama', 'Bishoftu', 'Nekemte', 'Ambo', 'Sebeta', 'Burayu', 'Holeta',
            'Sendafa', 'Dukem', 'Gelan', 'Sululta', 'Legetafo', 'Alem Gena',
            'Mojo', 'Meki', 'Ziway', 'Batu', 'Adami Tulu', 'Shashamane',
            'Asella', 'Bekoji', 'Robe', 'Goba', 'Dodola', 'Kofele', 'Assasa',
            'Ginir', 'Bule Hora', 'Yabelo', 'Negele Borana', 'Negele', 'Fiche',
            'Gerba Guracha', 'Addis Alem', 'Waliso', 'Ameya', 'Gimbi', 'Nejo',
            'Mendi', 'Dembi Dolo', 'Shambu', 'Bedele', 'Metu', 'Gore', 'Agaro',
            'Jimma', 'Chiro', 'Bedesa', 'Hirna', 'Deder', 'Haramaya',
            'Chelenko', 'Mieso', 'Mojena', 'Tulu Dimtu', 'Tefki', 'Tafo',
        ],
        'Sidama' => [
            'Hawassa', 'Yirgalem', 'Aleta Wondo', 'Wonsho', 'Shebedino',
            'Arbegona', 'Bensa', 'Dara', 'Chuko', 'Loko Abaya', 'Dale', 'Hula',
            'Gorche', 'Malga', 'Arorosa', 'Boricha', 'Bursa', 'Wondo Genet',
            'Aleta', 'Bona',
        ],
        'Somali' => [
            'Jigjiga', 'Gode', 'Kebri Dehar', 'Degehabur', 'Warder', 'Fik',
            'Kelafo', 'Mustahil', 'Kebri Beyah', 'Shinile', 'Tog Wajaale',
            'Wajaale', 'Harshin', 'Gashamo', 'Babile', 'Awbare', 'Tuli Guled',
            'Hargele', 'Dolobay', 'Bare', 'Adadle', 'Gursum', 'Amud', 'Tukh',
            'Borama', 'Fafen', 'Danan', 'El Kere',
        ],
        'Southern Nations' => [
            'Arba Minch', 'Dilla', 'Sawla', 'Chencha', 'Yirga Chefe', 'Jinka',
            'Turmi', 'Key Afer', 'Sodo', 'Konso', 'Karat', 'Gidole', 'Kemba',
            'Zala', 'Basketo', 'Gedeb', 'Wenago', 'Kochore', 'Bule', 'Dimeka',
            'Omorate', 'Gelila', 'Amaro',
        ],
        'South West Ethiopia' => [
            'Bonga', 'Tepi', 'Mizan Teferi', 'Masha', 'Gimbo', 'Decha', 'Chena',
            'Bita', 'Guraferda', 'Sheko', 'Maji', 'Dawro', 'Tercha', 'Waka',
            'Teppi',
        ],
        'Tigray' => [
            'Mekelle', 'Adigrat', 'Aksum', 'Adwa', 'Shire', 'Wukro', 'Alamata',
            'Maychew', 'Hawzen', 'Abi Adi', 'Korem', 'Sheraro', 'Humera',
            'May Tsebri', 'Nebelet', 'Zalambessa', 'Enticho', 'Bizet',
            'Freweyni', 'Hagere Selam', 'Samre', 'Seleqa',
        ],
    ];
}

function ethio_region_names() {
    return array_keys(ethio_regions());
}

/* Maps region names as written in the official Ethiopia admin-boundaries data
   (ArcGIS "Ethiopia_AdminBoundaries") to the canonical names used in
   ethio_regions(). Boundary datasets spell several regions differently
   (Oromiya/Oromia, Dredewa/Dire Dawa, Addis Abeba/Addis Ababa, etc.). */
function ethio_boundary_region_map() {
    return [
        'Oromiya'   => 'Oromia',
        'Oromia'    => 'Oromia',
        'Addis Abeba'  => 'Addis Ababa',
        'Addis  Abeba' => 'Addis Ababa',
        'Addis Ababa'  => 'Addis Ababa',
        'Tigray'    => 'Tigray',
        'Tigrai'    => 'Tigray',
        'Amhara'    => 'Amhara',
        'Afar'      => 'Afar',
        'Somali'    => 'Somali',
        'Sidama'    => 'Sidama',
        'Gambella'  => 'Gambella',
        'Gambela'   => 'Gambella',
        'Harari'    => 'Harari',
        'Harar'     => 'Harari',
        'Dredewa'   => 'Dire Dawa',
        'Dire Dawa' => 'Dire Dawa',
        'Benishangul Gumuz'  => 'Benishangul-Gumuz',
        'Benishangul-Gumuz'  => 'Benishangul-Gumuz',
        'SNNP'      => 'Southern Nations',
        'SNNPR'     => 'Southern Nations',
        'Southern Nations, Nationalities, and Peoples' => 'Southern Nations',
        'Southern Nations, Nationalities and Peoples'  => 'Southern Nations',
        'Southern Nations'  => 'Southern Nations',
        'South West Ethiopia Peoples' => 'South West Ethiopia',
        'South West Ethiopia' => 'South West Ethiopia',
        'Central Ethiopia'  => 'Central Ethiopia',
        'Centeral Ethiopia' => 'Central Ethiopia',
        'Amahara'   => 'Amhara',
        'OROMIYA'   => 'Oromia',
        'SOMALE KILLIL' => 'Somali',
    ];
}

/* Maps woreda/town names as written in the official boundary data to the
   canonical city names used in ethio_regions(). The boundary layer uses many
   local spellings ("Awassa", "Dese", "Asela", "Wekero", "Zeway", ...) that
   differ from the app's names, so the location picker can still auto-fill. */
function ethio_city_aliases() {
    return [
        'Awassa'             => 'Hawassa',
        'Awasa'              => 'Hawassa',
        'Dese'               => 'Dessie',
        'Asela'              => 'Asella',
        'Akisum'             => 'Aksum',
        'Adi Girat'          => 'Adigrat',
        'Abiyi Adi'          => 'Adigrat',
        'Wekero'             => 'Wukro',
        'Zeway'              => 'Ziway',
        'Weliso'             => 'Waliso',
        'Dila'               => 'Dilla',
        'Sawula'             => 'Sawla',
        'Weleikite'          => 'Wolkite',
        'Mizan Aman'         => 'Mizan Teferi',
        'Harer'              => 'Harar',
        'Shiraro'            => 'Sheraro',
        'Debrebrehan'        => 'Debre Birhan',
        'Shire Enida Silase' => 'Shire',
        'Wendo Genet'        => 'Wondo Genet',
        'Gambela'            => 'Gambella',
        'Haromaya'           => 'Haramaya',
        'Bale Robe'          => 'Robe',
        'Assela'             => 'Asella',
        'Jimma Spe Town'     => 'Jimma',
    ];
}

/* HTML <link>/<script> tags that load Leaflet for the location picker and the
   map shown on the house detail page. Echo into the page <head>. */
function locations_map_assets() {
    return '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">' . "\n"
         . '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>' . "\n";
}

/* Location picker: an interactive Leaflet map wired to the shared form fields
   used by post_house.php and edit_house.php (region #f-region, city #f-city,
   map link #map_link, kebele input[name="kebele"]). Clicking on the map (or
   pressing "use my location") stores WGS84 coordinates in #loc_lat/#loc_lng and
   reverse-geocodes the point against the official ArcGIS kebele boundaries to
   auto-fill Region, City and a numeric Kebele. It never overwrites the address
   fields unless the detected values are valid for the form's canonical lists. */
function locations_picker_js() {
    $aliases  = json_encode(ethio_boundary_region_map(), JSON_UNESCAPED_UNICODE);
    $caliases = json_encode(ethio_city_aliases(), JSON_UNESCAPED_UNICODE);
    $arcgis   = 'https://services8.arcgis.com/oTalEaSXAuyNT7xf/arcgis/rest/services/Ethiopia_AdminBoundaries/FeatureServer/0/query';
    $arcgis_q = json_encode($arcgis, JSON_UNESCAPED_SLASHES);
    $adama    = [8.54, 39.27];

    $labels = json_encode([
        'detected'     => t('ph_loc_detected'),
        'geoUnsupported' => t('ph_loc_geo_unsupported'),
        'geoDenied'    => t('ph_loc_geo_denied'),
        'selectCity'   => t('select_city'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return <<<JS
<script>
window.ETHIO_BOUNDARY_REGION_ALIAS = {$aliases};
window.ETHIO_CITY_ALIAS = {$caliases};
(function(){
    var ADAMA_LAT = {$adama[0]}, ADAMA_LNG = {$adama[1]};
    var LABELS = {$labels};
    var ARCGIS_URL = {$arcgis_q};

    var mapEl  = document.getElementById('locMap');
    var latEl  = document.getElementById('loc_lat');
    var lngEl  = document.getElementById('loc_lng');
    if(!mapEl || !latEl || !lngEl || typeof L === 'undefined') return;

    var map = L.map(mapEl).setView([ADAMA_LAT, ADAMA_LNG], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    var marker = null;

    /* Repopulate the City select after Region changes. cascadeRegionCity() is
       defined by locations_cascade_js() and returns its fillCities closure. */
    var regionSel = document.getElementById('f-region');
    var citySel = document.getElementById('f-city');
    var fillCities = (typeof cascadeRegionCity === 'function' && regionSel && citySel)
        ? cascadeRegionCity('f-region', 'f-city', LABELS.selectCity)
        : function(){};

    function wgsToWebMercator(lat, lng){
        var R = 6378137;
        var x = Math.PI / 180 * lng * R;
        var y = Math.log(Math.tan(Math.PI / 4 + Math.PI / 180 * lat / 2)) * R;
        return [x, y];
    }

    /* Turn "Arba Minch Town", "Debrebrehan Town", "Jimma Spe Town" etc. into a
       comparable token so it can be matched against the canonical city list. */
    function normName(s){
        return String(s || '').toUpperCase().replace(/\s+/g, ' ')
            .replace(/(TOWN|KETEMA|CITY|SPECIAL|SPE|ZONE)\s*$/, '').trim();
    }

    function canonicalRegion(bName){
        var raw = String(bName || '').trim();
        if(!raw) return '';
        if(window.ETHIO_BOUNDARY_REGION_ALIAS && window.ETHIO_BOUNDARY_REGION_ALIAS[raw]) {
            return window.ETHIO_BOUNDARY_REGION_ALIAS[raw];
        }
        var nm = raw.toUpperCase().replace(/\s+/g, ' ');
        for(var k in window.ETHIO_BOUNDARY_REGION_ALIAS){
            if(k.toUpperCase().replace(/\s+/g, ' ') === nm) return window.ETHIO_BOUNDARY_REGION_ALIAS[k];
        }
        return raw;
    }

    function cityAlias(name){
        var n = normName(name);
        var map = window.ETHIO_CITY_ALIAS || {};
        for(var k in map){
            if(normName(k) === n) return map[k];
        }
        return '';
    }

    /* Find the best matching city for a boundary woreda/town name: exact
       normalized name, then a known spelling alias. */
    function matchCity(cities, want){
        if(!want) return '';
        var i;
        for(i = 0; i < cities.length; i++){
            if(normName(cities[i]) === want) return cities[i];
        }
        var al = normName(cityAlias(want));
        if(al){
            for(i = 0; i < cities.length; i++){
                if(normName(cities[i]) === al) return cities[i];
            }
        }
        return '';
    }

    function applyBoundary(attrs){
        if(!attrs || !regionSel || !citySel || !window.ETHIO_LOCATIONS) return;
        var bRegion = (attrs.R_NAME || '').trim();
        var canonical = canonicalRegion(bRegion);
        if(!canonical || !window.ETHIO_LOCATIONS[canonical]) return;

        var changed = false;
        if(!regionSel.value || regionSel.value !== canonical){
            regionSel.value = canonical;
            fillCities();
            changed = true;
        }

        var want = normName(attrs.W_NAME);
        var matched = matchCity(window.ETHIO_LOCATIONS[canonical] || [], want);
        if(!matched){
            for(var reg in window.ETHIO_LOCATIONS){
                var m = matchCity(window.ETHIO_LOCATIONS[reg], want);
                if(m){
                    matched = m;
                    if(reg !== canonical){
                        canonical = reg;
                        regionSel.value = reg;
                        fillCities();
                        changed = true;
                    }
                    break;
                }
            }
        }
        if(matched){
            if(citySel.value !== matched){ citySel.value = matched; changed = true; }
        }

        var kk = (attrs.KK_NAME || '').trim();
        var kb = document.querySelector('input[name="kebele"]');
        if(kb && kb.value.trim() === '' && kk && /^[0-9\- ]{1,12}$/.test(kk)){
            kb.value = kk;
            changed = true;
        }

        var note = document.getElementById('loc-detected');
        if(note){
            var parts = [attrs.W_NAME, attrs.Z_NAME, bRegion].filter(function(x){
                x = String(x || '').trim();
                return x !== '' && x !== ' ';
            });
            note.textContent = LABELS.detected + ': ' + parts.join(', ');
            note.style.display = 'block';
        }

        if(changed && typeof updateMapLink === 'function') updateMapLink();
    }

    function reverseGeocode(lat, lng){
        var wm = wgsToWebMercator(lat, lng);
        var url = ARCGIS_URL
            + '?where=1%3D1'
            + '&geometry=' + wm[0].toFixed(2) + ',' + wm[1].toFixed(2)
            + '&geometryType=esriGeometryPoint&inSR=102100'
            + '&outFields=R_NAME,Z_NAME,W_NAME,T_NAME,KK_NAME'
            + '&returnGeometry=false&f=json';
        if(window.fetch){
            fetch(url).then(function(r){ return r.json(); }).then(function(d){
                if(d && d.features && d.features.length) applyBoundary(d.features[0].attributes);
            }).catch(function(){});
        }
    }

    function setMarker(lat, lng, pan){
        latEl.value = lat.toFixed(6);
        lngEl.value = lng.toFixed(6);
        if(marker){
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
        if(pan) map.setView([lat, lng], Math.max(map.getZoom(), 15));
        reverseGeocode(lat, lng);
        if(typeof updateMapLink === 'function') updateMapLink();
    }

    map.on('click', function(e){
        setMarker(e.latlng.lat, e.latlng.lng, false);
    });

    var locBtn = document.getElementById('loc-use');
    if(locBtn && navigator.geolocation){
        locBtn.addEventListener('click', function(){
            navigator.geolocation.getCurrentPosition(function(p){
                setMarker(p.coords.latitude, p.coords.longitude, true);
            }, function(){
                alert(LABELS.geoDenied);
            }, { enableHighAccuracy: true, timeout: 12000 });
        });
    } else if(locBtn){
        locBtn.addEventListener('click', function(){ alert(LABELS.geoUnsupported); });
    }

    var initLat = parseFloat(latEl.value);
    var initLng = parseFloat(lngEl.value);
    if(!isNaN(initLat) && !isNaN(initLng)){
        marker = L.marker([initLat, initLng]).addTo(map);
        map.setView([initLat, initLng], 15);
    }
})();
</script>
JS;
}

/* Returns the cities for a region, or an empty array when the region is
   unknown/blank so callers can safely render an empty select. */
function ethio_cities($region) {
    $all = ethio_regions();
    return isset($all[$region]) ? $all[$region] : [];
}

/* Merges the curated region->cities map with the distinct region/city values
   actually stored in the houses table. The curated list is only a starting
   point: a house may carry a town that was saved through the map picker (or a
   spelling not in the list), and without this merge that town never shows up in
   the Region/City selects, so it can't be filtered and appears "missing".
   Region/city names are compared case-insensitively and duplicates are not
   added again. */
function ethio_regions_merged($conn) {
    $regions = ethio_regions();
    $res = mysqli_query($conn, "SELECT DISTINCT region, city FROM houses WHERE city <> ''");
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $city = trim((string)($row['city'] ?? ''));
            if($city === '') continue;
            $region = trim((string)($row['region'] ?? ''));
            if($region === '') continue;

            $key = $region;
            foreach(array_keys($regions) as $name) {
                if(mb_strtolower($name) === mb_strtolower($region)) { $key = $name; break; }
            }

            if(!isset($regions[$key])) $regions[$key] = [];
            $exists = false;
            foreach($regions[$key] as $c) {
                if(mb_strtolower($c) === mb_strtolower($city)) { $exists = true; break; }
            }
            if(!$exists) $regions[$key][] = $city;
        }
    }
    return $regions;
}

/* Emits the region->city payload plus cascadeRegionCity(), which repopulates a
   City select whenever its Region select changes. $regionId/$cityId are the DOM
   ids of the two selects and $emptyLabel is the City's placeholder option.
   The previously selected city is kept when it is still valid for the region,
   and cleared otherwise, so a stale city can never be submitted.
   Pass a custom $regions map (e.g. ethio_regions_merged()) to include towns
   that only exist in the database. When $allWhenBlank is true the City select
   lists every town from every region (grouped by region) while no region is
   chosen, so a town can be picked without first narrowing by region. */
function locations_cascade_js($regionId, $cityId, $emptyLabel, array $regions = null, $allWhenBlank = false) {
    if($regions === null) $regions = ethio_regions();
    $payload  = json_encode($regions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $empty    = json_encode($emptyLabel, JSON_UNESCAPED_UNICODE);
    $allFlag  = $allWhenBlank ? 'true' : 'false';

    return <<<JS
<script>
window.ETHIO_LOCATIONS = {$payload};
function cascadeRegionCity(regionId, cityId, emptyLabel){
    var regionSel = document.getElementById(regionId);
    var citySel = document.getElementById(cityId);
    if(!regionSel || !citySel) return;
    var allWhenBlank = {$allFlag};

    function appendCities(parent, cities){
        cities.forEach(function(name){
            var opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            parent.appendChild(opt);
        });
    }

    function fillCities(){
        var map = window.ETHIO_LOCATIONS || {};
        var prev = citySel.value;
        citySel.textContent = '';

        var blank = document.createElement('option');
        blank.value = '';
        blank.textContent = emptyLabel;
        citySel.appendChild(blank);

        if(!regionSel.value && allWhenBlank){
            Object.keys(map).forEach(function(reg){
                var cities = map[reg] || [];
                if(!cities.length) return;
                var group = document.createElement('optgroup');
                group.label = reg;
                appendCities(group, cities);
                citySel.appendChild(group);
            });
        } else {
            appendCities(citySel, map[regionSel.value] || []);
        }

        citySel.value = prev;
        if(citySel.selectedIndex < 0 && prev !== ''){
            var keep = document.createElement('option');
            keep.value = prev;
            keep.textContent = prev;
            citySel.appendChild(keep);
            citySel.value = prev;
        }
        if(citySel.selectedIndex < 0) citySel.value = '';
    }

    regionSel.addEventListener('change', fillCities);
    fillCities();
    return fillCities;
}
</script>
JS;
}