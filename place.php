
    <?php

    function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
      $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    if(is_ajax_request()) {
        // user enter the location information
        if(isset($_POST['otherLocation'])) {
            $urlOfMap = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($_POST['otherLocation'])."&key=AIzaSyCAOh4hsHZ7zKU-71Jn7yql0LcrsA_iVEM";
            $arrayOfMap = json_decode(file_get_contents($urlOfMap), true);
        // location entered does not exist
            if($arrayOfMap['status'] == "ZERO_RESULTS") {
                echo json_encode($arrayOfMap);
                exit;
            } else {
                $latGeo = $arrayOfMap['results']['0']['geometry']['location']['lat'];
                $lngGeo = $arrayOfMap['results']['0']['geometry']['location']['lng'];
                $location = $latGeo.",".$lngGeo;
            }
        } 
        // user checked "Here" button
        else {
            $location = $_POST['location'];
            $urlOfMap = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($_POST['location'])."&key=AIzaSyCAOh4hsHZ7zKU-71Jn7yql0LcrsA_iVEM";
            $arrayOfMap = json_decode(file_get_contents($urlOfMap), true);
            $latGeo = $arrayOfMap['results']['0']['geometry']['location']['lat'];
            $lngGeo = $arrayOfMap['results']['0']['geometry']['location']['lng'];
        }

        $radius = $_POST['distance'] * 1600;
        $type = $_POST['category'];
        $keyword = $_POST['keyword'];

        if($type == 'default') {
            $urlOfPlace = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=".$location."&radius=".$radius."&keyword=".urlencode($keyword)."&key=AIzaSyCAOh4hsHZ7zKU-71Jn7yql0LcrsA_iVEM";
        }
        else {
            $urlOfPlace = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=".$location."&radius=".$radius."&type=".$type."&keyword=".urlencode($keyword)."&key=AIzaSyCAOh4hsHZ7zKU-71Jn7yql0LcrsA_iVEM";   
        }
        $jsonPlace = file_get_contents($urlOfPlace);
        $jsonPlace = json_decode($jsonPlace, true);
        $jsonPlace['latGeo'] = $latGeo;
        $jsonPlace['lngGeo'] = $lngGeo;
        $jsonPlace = json_encode($jsonPlace);
        echo $jsonPlace;
        exit;
    }

    if(isset($_GET['place_id'])) {
        $urlOfDetail = "https://maps.googleapis.com/maps/api/place/details/json?placeid=".$_GET['place_id']."&key=AIzaSyCAOh4hsHZ7zKU-71Jn7yql0LcrsA_iVEM";
        $jsonDetail = file_get_contents($urlOfDetail);
        $jsonDetail = json_decode($jsonDetail, true);
        //save up to 5 photos in the server
        if(!isset($jsonDetail['result']['photos'])) {
            $numPhoto = 0;
        } else {
            $countPhoto = count($jsonDetail['result']['photos']);
            if($countPhoto > 0 && $countPhoto < 5) {
                $numPhoto = $countPhoto;
            } else {
                $numPhoto = 5;
            }
            for($i = 0; $i < $numPhoto; $i++) {
                $photoReference = $jsonDetail['result']['photos'][$i]['photo_reference'];
                $urlOfPhoto = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=1000&photoreference=".$photoReference."&key=AIzaSyCAOh4hsHZ7zKU-71Jn7yql0LcrsA_iVEM";
                $photos = file_get_contents($urlOfPhoto);
                file_put_contents('photo'.$i.'.jpg', $photos);
            }
        }
        // gather data into $jsonDetail associative array
        // encode array, return the JSON object to the client side
        $jsonDetail['numPhoto'] = $numPhoto;
        $jsonObj = json_encode($jsonDetail);
        echo $jsonObj;
        exit;
    }

    ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Let's Search</title>
    <style type="text/css">

        form {
            background-color: #fafafa;
            border: 4px solid #cccccc;
            width: 790px;
            height: 258px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 45px;
            font-weight: 400;
            font-style: italic;
            margin: 5px 0 auto;
        }

        hr {
            border: 0 none;
            height: 2px;
            margin: 5px 10px;
            background-color: #b0b0b0;
        }

        label {
            font-size: 20px;
            font-weight: 600;
        }

        .input {
            padding: 10px;
            line-height: 25px;
        }

        .submit {
            position: absolute;
            top: 220px;
            left:420px;
        }

        .hideMap {
            display: none;
        }

        .showMap {
            display: block;
        }

        #map {
            position: absolute;
            left: 750px;
            top: 380px;
            height: 300px;
            width: 400px;  
        }

        #direction {
            position: absolute;
            left: 750px;
            top: 380px;
            z-index: 2;
            text-align: center;
            font-weight: 600;
            font-size: 15px;
            background-color: #f0f0f0;
        }

        #walk {
            position: relative;
            height: 40px;
            width: 100px; 
            line-height: 40px;
            cursor: pointer;
        }

        #walk:hover {
            background-color: #dcdcdc;
            transition: 0.2s linear;
        }

        #bike {
            position: relative;
            height: 40px;
            width: 100px; 
            line-height: 40px;
            cursor: pointer;
        }

        #bike:hover {
            background-color: #dcdcdc;
            transition: 0.2s linear;
        }        

        #drive {
            position: relative;
            height: 40px;
            width: 100px; 
            line-height: 40px;            
            cursor: pointer;
        }

        #drive:hover {
            background-color: #dcdcdc;
            transition: 0.2s linear;
        }

        #from {
            display: inline; 
            position: absolute; 
        }

        #vicinity a {
            color: black;
            text-decoration: none; 
        }

        #vicinity a:hover {
            color: rgb(200, 200, 200);
            transition: 0.2s linear;
        }

    </style>
</head>
<body>

    <!-- "return false": avoid submitting the form. -->
    <form method="post" action="place.php" id="searchForm" onsubmit="submitForm(event); return false">
        <h1>Travel and Entertainment Search</h1>
        <hr>
        <div class="input">
            <div>
                <label for="keyword">Keyword</label>
                <input id="keyword" type="text" name="keyword" required>        
            </div>

            <div>
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="default">default</option>
                    <option value="cafe">cafe</option>
                    <option value="bakery">bakery</option>
                    <option value="restaurant">restaurant</option>
                    <option value="beauty_salon">beauty salon</option>
                    <option value="casino">casino</option>
                    <option value="movie_theater">movie theater</option>
                    <option value="lodging">lodging</option>
                    <option value="airport">airport</option>
                    <option value="train_station">train station</option>
                    <option value="subway_station">subway station</option>
                    <option value="bus_station">bus station</option>
                </select>            
            </div>

            <div>
                <label for="distance">Distance (miles)</label>
                <input id="distance" type="text" name="distance" placeholder="10">

                <label for="from">
                    from
                    <div id="from">
                        <input id="here" type="radio" name="location" value="" checked onchange="enableHere()"><span style="font-weight: 400">Here</span>
                        <br>
                        <input id="not_here" type="radio" name="location" value="" onchange="enableOther()"><input id="otherLocation" type="text" name="otherLocation" placeholder="location" disabled>
                    </div>
                </label>

            </div>
            <div class="submit">
                <input id="search" type="submit" name="search" value="Search" style="font-size: 15px" disabled>
                <input id="clear" type="button" name="clear" value="Clear" style="font-size: 15px" onclick="clearAll()">
            </div>        
        </div>
    </form>
    <br>


    <script type="text/javascript">

        window.onload = fetchGeo();

        function init(lat, lon) {
            document.getElementById("here").value = lat + "," + lon;
            document.getElementById("distance").value = "10";
            document.getElementById("search").disabled = false;
        }

        // fetch geolocation and set the default value of search form
        function fetchGeo() {
            var xhr = new XMLHttpRequest();
            // initialization, synchronously
            xhr.open("get", "http://ip-api.com/json", false);
            xhr.onreadystatechange = function() {
                if(xhr.readyState == 4 && xhr.status == 200) {
                    var jsonDoc = xhr.responseText;
                    jsonDoc = JSON.parse(jsonDoc);
                    lat = jsonDoc.lat;
                    lon = jsonDoc.lon;         
                    init(lat, lon);
                }
            };
            // send request
            xhr.send(); 
        }

        function enableHere() {
            document.getElementById("otherLocation").disabled = true;
        }

        function enableOther() {
            document.getElementById("otherLocation").disabled = false;
            document.getElementById("otherLocation").required = true;
        }

        function clearAll() {
            document.getElementById("searchForm").reset();
            init(lat, lon);
            document.getElementById("otherLocation").disabled = true;
            if(document.getElementById("div") != null) {
                document.getElementById("div").innerHTML = "";    
            }
        }

        function submitForm(event) {
            var form = document.getElementById("searchForm");
            // gather form data
            var formData = new FormData(form);
            for([key, value] of formData.entries()) {
              console.log(key + ': ' + value);
            }
            var xhr = new XMLHttpRequest();
            // use AJAX to post form data
            xhr.open("post", "place.php", true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function() {
                if(xhr.readyState == 4 && xhr.status == 200) {
                    var jsonPlace = xhr.responseText;
                    jsonPlace = JSON.parse(jsonPlace);

                    if(jsonPlace.status == "ZERO_RESULTS") {
                        html_text = "<div style='background-color: #f0f0f0; width: 900px; margin: 0 auto; border: 2px solid #cccccc; font-size: 20px; text-align: center;'>No Record has been found</div>";
                    } else {
                        var rows = jsonPlace.results;
                        var startLat = jsonPlace.latGeo;
                        var startLng = jsonPlace.lngGeo;
                        if(rows.length == 0) {
                            html_text = "<div style='background-color: #f0f0f0; width: 900px; margin: 0 auto; border: 2px solid #cccccc; font-size: 20px; text-align: center;'>No Record has been found</div>";
                        }
                        else {
                            html_text = "<table border='1' style='margin: 0 auto'><thead style='font-size: 20px'><tr>";
                            html_text +="<th style='width: 150px'>Category</th>";
                            html_text +="<th style='width: 450px'>Name</th>";
                            html_text +="<th style='width: 600px'>Address</th>";
                            html_text += "</tr></thead>";
                            html_text += "<tbody>";
                            for(var i = 0; i < rows.length; i++) {     
                                placeObj = rows[i];
                                html_text += "<tr style='font-size: 20px'>";
                                html_text += "<td style='padding-left: 20px'><img src='" + placeObj["icon"] + "'</td>";
                                html_text += "<td style='padding-left: 20px'><a href='javaScript:void(0)' style='text-decoration: none; color: black;' onclick='showDetail(this)' id='" + placeObj["place_id"] + "'>" + placeObj["name"] + "</a></td>";
                                html_text += "<td  id='vicinity' style='padding-left: 20px'><a href='javaScript:void(0)' onclick='initMap(" + placeObj['geometry']['location']['lat'] + ", " + placeObj['geometry']['location']['lng'] + ", " + i + ", " + startLat + ", " + startLng + ")'>" + placeObj["vicinity"] + "</a></td>";
                                html_text += "</tr></tbody>";
                            }
                            html_text += "<div id='map' class='hideMap'></div>";
                            html_text += "<div id='direction' class='hideMap'>";
                            html_text += "<div id='walk'>Walk there</div>";
                            html_text += "<div id='bike'>Bike there</div>";
                            html_text += "<div id='drive'>Drive there</div>";
                            html_text += "</div>";
                        }      
                    }
                    var div = document.createElement('div');
                    div.setAttribute("id", "div");
                    document.body.appendChild(div);
                    document.getElementById("div").innerHTML = html_text;
                } 
            };
            xhr.send(formData);
        }

        function showDetail(element) {
            var selectedPlace = element.getAttribute('id');
            var xhr = new XMLHttpRequest();
            var url = "place.php?place_id=" + selectedPlace + "&para2=" + Math.random();
            xhr.open("get", url, true);
            xhr.onreadystatechange = function() {
                if(xhr.readyState == 4 && xhr.status == 200) {
                    var jsonObj = xhr.responseText;
                    jsonObj = JSON.parse(jsonObj);
                    var numPhoto = jsonObj.numPhoto;
                    // create the menu of photos and reviews
                    placeName = element.textContent;
                    html_text = "<div style='font-size: 25px; font-weight: 600; padding-top: 10px; padding-bottom: 50px; text-align: center;'>" + placeName + "</div>";
                    html_text += "<div style='font-size: 20px; text-align: center;'>click to show reviews</div>" ;
                    html_text += "<div id='reviewButton' style='text-align: center'><a href='javaScript:void(0)' onclick='showReview()'><img src='http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png' width='40px'></a></div>";
                    html_text += "<div id='reviewList' style='display: none'></div>";
                    html_text += "<br>";
                    html_text += "<div style='font-size: 20px; text-align: center;'>click to show photos</div>";
                    html_text += "<div id='photoButton' style='text-align: center'><a href='javaScript:void(0)' onclick='showPhoto(" + numPhoto + ")'><img src='http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png' width='40px'></a></div>";
                    html_text += "<div id='photoList' style='display: none'></div>";
                    document.getElementById("div").innerHTML = html_text;

                    // set up the review list
                    var resultObj = jsonObj.result;
                    if(resultObj.reviews === undefined || resultObj.reviews.length == 0) {
                        html_text = "<div style='width: 800px; margin: 0 auto; border: 2px solid #cccccc; font-size: 20px; font-weight: 600; text-align: center;'>No Reviews Found</div>";
                            document.getElementById("reviewList").innerHTML = html_text;
                    } else {
                        html_text = "<table border = '1' style='margin: 0 auto'; width='800px'><tr>";
                        if(resultObj.reviews.length > 0 && resultObj.reviews.length < 5) {
                            numReview = resultObj.reviews.length;
                        } else {
                            numReview = 5;
                        }
                        for(var i = 0; i < numReview; i++) {
                            html_text += "<td style='text-align: center; font-weight: 600; font-size: 20px'><img src='" + resultObj.reviews[i].profile_photo_url + "' width='40px'>" + resultObj.reviews[i].author_name + "</td></tr>";
                            html_text += "<tr><td style='font-size: 20px'>" + resultObj.reviews[i].text + "</td></tr>";
                        }
                        html_text += "</table>";
                        document.getElementById("reviewList").innerHTML = html_text;
                    }
                }
            };
            xhr.send();
        }

        function showReview() {
            document.getElementById("reviewButton").innerHTML = "<a href='javaScript:void(0)' onclick='hideReview()'><img src='http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png' width='40px'></a>";
            document.getElementById("reviewList").style.display = "block"; 
        }

        function showPhoto(numPhoto) {
            document.getElementById("photoButton").innerHTML = "<a href='javaScript:void(0)' onclick='hidePhoto(" + numPhoto + ")'><img src='http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png' width='40px'></a>";
            if(numPhoto == 0) {
                html_text = "<div style='width: 800px; margin: 0 auto; border: 2px solid #cccccc; font-size: 20px; font-weight: 600; text-align: center;'>No Photos Found</div>";
                document.getElementById("photoList").innerHTML = html_text;
            } else {
                html_text = "<table border='1' style='margin: 0 auto; width: 800px'><tr>";
                for(var i = 0; i < numPhoto; i++) {
                    html_text += "<td style='text-align: center'><a href='photo" + i + ".jpg' target='_blank'><img src='photo" + i + ".jpg?ran=" + Math.random() + "' style='padding: 20px' width='730px'></a></td></tr>";
                }
                html_text += "</table>";
                document.getElementById("photoList").innerHTML = html_text;
            }
            document.getElementById("photoList").style.display = "block"; 
        }

        function hideReview() {
            document.getElementById('reviewList').style.display = "none";
            document.getElementById("reviewButton").innerHTML = "<a href='javaScript:void(0)' onclick='showReview()'><img src='http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png' width='40px'></a>";
        }

        function hidePhoto(numPhoto) {
            document.getElementById('photoList').style.display = "none";
            document.getElementById("photoButton").innerHTML = "<a href='javaScript:void(0)' onclick='showPhoto(" + numPhoto + ")'><img src='http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png' width='40px'></a>";
        }
        
        function initMap(latitude, longitude, order, startLat, startLng) {
            var directionsDisplay = new google.maps.DirectionsRenderer();
            var directionsService = new google.maps.DirectionsService();
            var markerLocation = {lat: latitude, lng: longitude};
            var mapOptions = {
                zoom: 12,
                center: markerLocation    
            }
            var map = new google.maps.Map(document.querySelector("#map"), mapOptions);
            directionsDisplay.setMap(map);
            displayMap(order);
            // display marker
            var marker = new google.maps.Marker({
                position: markerLocation,
                map: map
            });
            document.getElementById("walk").addEventListener("click", function() {
                marker.setMap(null);
                calcRoute(latitude, longitude, startLat, startLng, directionsDisplay, directionsService, "WALKING");
            });
            document.getElementById("bike").addEventListener("click", function() {
                marker.setMap(null);
                calcRoute(latitude, longitude, startLat, startLng, directionsDisplay, directionsService, "BICYCLING");
            });
            document.getElementById("drive").addEventListener("click", function() {
                marker.setMap(null);
                calcRoute(latitude, longitude, startLat, startLng, directionsDisplay, directionsService, "DRIVING");
            });
        }

        orderPhoto = -1;
        function displayMap(order) {
            // display the first time
            if(orderPhoto == -1) {
                sizeMap = order * 84 + 380;
                document.querySelector("#map").style.top = sizeMap + "px";
                document.querySelector("#direction").style.top = sizeMap + "px";
                document.querySelector("#map").classList.add("showMap");
                document.querySelector("#direction").classList.add("showMap");
            } else {
            // if the map is open
                if(document.querySelector("#map").classList.contains("showMap")) {
                    if(order != orderPhoto) {
                        document.querySelector("#map").classList.remove("showMap");
                        document.querySelector("#direction").classList.remove("showMap");
                        sizeMap = order * 84 + 380;
                        document.querySelector("#map").style.top = sizeMap + "px";
                        document.querySelector("#direction").style.top = sizeMap + "px";
                        document.querySelector("#map").classList.add("showMap");
                        document.querySelector("#direction").classList.add("showMap");
                    } else {
                        document.querySelector("#map").classList.remove("showMap");
                        document.querySelector("#direction").classList.remove("showMap");
                    }
            // if the map is closed
                } else {
                    if(order != orderPhoto) {
                        sizeMap = order * 84 + 380;
                        document.querySelector("#map").style.top = sizeMap + "px";
                        document.querySelector("#direction").style.top = sizeMap + "px";
                        document.querySelector("#map").classList.add("showMap");
                        document.querySelector("#direction").classList.add("showMap");
                    } else {
                        document.querySelector("#map").classList.add("showMap");
                        document.querySelector("#direction").classList.add("showMap");
                    }
                }
            }
            orderPhoto = order;
        }

        function calcRoute(latitude, longitude, startLat, startLng, directionsDisplay, directionsService, mode) {
            var start = new google.maps.LatLng(startLat, startLng);
            var end = new google.maps.LatLng(latitude, longitude);
            var request = {
                origin: start,
                destination: end,
                travelMode: mode
            };
            directionsService.route(request, function(result, status) {
                if (status == 'OK') {
                  directionsDisplay.setDirections(result);
                }
            });
        }


    </script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDhC1Tha8FKORJfe7--SYluRWe_n1LVMoE">
    </script>
</body>
</html>