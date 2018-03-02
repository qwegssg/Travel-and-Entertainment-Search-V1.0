
    <?php
    $jsonPlace = 0;
    if(isset($_POST['search'])) {
        // user enter the location information
        if(isset($_POST['otherLocation'])) {
            $urlOfMap = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($_POST['otherLocation'])."&key=AIzaSyDhC1Tha8FKORJfe7--SYluRWe_n1LVMoE";
            $arrayOfMap = json_decode(file_get_contents($urlOfMap), true);


            // what if the location entered does not exist? Wait to be dealed!!!!!!!!
            if($arrayOfMap['status'] == "ZERO_RESULTS") {

                // $jsonPlace = "ZERO_RESULTS";
                echo "Location does not exist!!!!";
                echo "<div style='background-color: #f0f0f0; width: 900px; margin: 0 auto; border: 2px solid #cccccc; font-size: 20px; text-align: center;'>No Record has been found</div>";
                exit;
                // $location = "0,0";
                // return;
            } else {
                $latGeo = $arrayOfMap['results']['0']['geometry']['location']['lat'];
                $lngGeo = $arrayOfMap['results']['0']['geometry']['location']['lng'];
                $location = $latGeo.",".$lngGeo;
            }
        } 
        // user checked "Here" button
        else {
            $location = $_POST['location'];
        }

        $radius = $_POST['distance'] * 1600;
        $type = $_POST['category'];
        $keyword = $_POST['keyword'];

        if($type == 'default') {
            $urlOfPlace = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=".$location."&radius=".$radius."&keyword=".urlencode($keyword)."&key=AIzaSyDhC1Tha8FKORJfe7--SYluRWe_n1LVMoE";
        }
        else {
            $urlOfPlace = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=".$location."&radius=".$radius."&type=".$type."&keyword=".urlencode($keyword)."&key=AIzaSyDhC1Tha8FKORJfe7--SYluRWe_n1LVMoE";   
        }
        $jsonPlace = file_get_contents($urlOfPlace);
        $jsonPlace = json_encode($jsonPlace);
    }

    if(isset($_GET['place_id'])) {
        $arrayOfPlace = json_decode($jsonPlace, true);
        $urlOfDetail = "https://maps.googleapis.com/maps/api/place/details/json?placeid=".$_GET['place_id']."&key=AIzaSyDhC1Tha8FKORJfe7--SYluRWe_n1LVMoE";
        $jsonDetail = file_get_contents($urlOfDetail);
        // decode the JSON string to php variables
        $jsonDetailPhoto = json_decode($jsonDetail, true);

        // if there is no photo about the place
        if(!isset($jsonDetailPhoto['result']['photos'])) {
            $numPhoto = 0;
        } else {
            $countPhoto = count($jsonDetailPhoto['result']['photos']);
            if($countPhoto > 0 && $countPhoto < 5) {
                $numPhoto = $countPhoto;
            } else {
                $numPhoto = 5;
            }
            for($i = 0; $i < $numPhoto; $i++) {
                $photoReference = $jsonDetailPhoto['result']['photos'][$i]['photo_reference'];
                $urlOfPhoto = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=1000&photoreference=".$photoReference."&key=AIzaSyDhC1Tha8FKORJfe7--SYluRWe_n1LVMoE";
                $photos = file_get_contents($urlOfPhoto);
                file_put_contents('photo'.$i.'.jpg', $photos);
            }
        }
        echo $numPhoto;
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

        #from {
            display: inline; 
            position: absolute; 
        }

    </style>

</head>
<body>

    <form method="post" action="place.php" id="searchForm">
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
                <input id="clear" type="submit" name="clear" value="Clear" style="font-size: 15px">
            </div>        
        </div>
    </form>
    <br>


    

    <script type="text/javascript">

        window.onload = fetchGeo();




        // keep the input value????

        // function searchValue() {
        //     var keyword = document.getElementById("keyword").value;
        //     var otherLocation = document.getElementById("otherLocation").value;
        //     var xhr = new XMLHttpRequest();

        //     xhr.onreadystatechange = function() {
        //     if (this.readyState == 4 && this.status == 200) {
        //         console.log("123456");
        //         document.getElementById("keyword").value = this.responseText;
        //         // document.getElementById("otherLocation").value = otherLocation;
        //     }
        //     arg = "keyword=" + keyword;
        //     xhr.open("post", "place.php", true);
        //     // for a "post" request, the Header must be set
        //     xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        //     // send form data as argument to send()
        //     xhr.send(arg);
        //     };
        // }

        
        // Set the current location, set the default distance and enable the search button after fetching location.
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

        // need to work on!!!!!
        function showDetail(element) {
            var selectedPlace = element.getAttribute('id');
            var xhr = new XMLHttpRequest();
            var url = "place.php?place_id=" + selectedPlace + "&para2=" + Math.random();
            xhr.open("get", url, true);
            xhr.onreadystatechange = function() {
                if(xhr.readyState == 4 && xhr.status == 200) {
                    var numPhoto = xhr.responseText;
                    if(numPhoto == 0) {
                        html_text = "<div style='width: 800px; margin: 0 auto; border: 2px solid #cccccc; font-size: 20px; font-weight: 600; text-align: center;'>No Photos Found</div>";
                        document.getElementById("div").innerHTML = html_text;
                    } else {
                        html_text = "<table border='1' style='margin: 0 auto; width: 800px'><tr>";
                        for(var i = 0; i < numPhoto; i++) {
                            html_text += "<td style='text-align: center'><img src='photo" + i + ".jpg' style='padding: 20px' width='700px'></td></tr>";
                        }
                    }
                    html_text += "</table>";
                    document.getElementById("div").innerHTML = html_text;
                }
            };
            xhr.send();
        }



        // construct the place table
        jsonPlace = <?php echo $jsonPlace; ?>;
        if(jsonPlace != 0) {
            jsonPlace = JSON.parse(jsonPlace);
            var rows = jsonPlace.results;
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
                    html_text += "<td style='padding-left: 20px'><a href='javaScript:void(0)'style='text-decoration: none; color: black;' onclick='showDetail(this)' id='" + placeObj["place_id"] + "'>" + placeObj["name"] + "</a></td>";
                    html_text += "<td style='padding-left: 20px'>" + placeObj["vicinity"] + "</td>";
                    html_text += "</tr></tbody>";
                }
            }      
            var div = document.createElement('div');
            div.setAttribute("id", "div");
            document.body.appendChild(div);
            document.getElementById("div").innerHTML = html_text;  
        } 

        // Create table without refresh page????

        //         var button = document.getElementById("search");
        // button.addEventListener("click", searchPlace);
        // function searchPlace() {
            
        //     var form = document.getElementById("searchForm");
        //     var action = form.getAttribute("action");

        //     // gather form data
        //     var formData = new FormData(form);
        //     for([key, value] of formData.entries()) {
        //       console.log(key + ': ' + value);
        //     }

        //     var xhr = new XMLHttpRequest();
        //     xhr.open('POST', action, true);
        //     xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        //     xhr.onreadystatechange = function () {
        //         if(xhr.readyState == 4 && xhr.status == 200) {
        //         var result = xhr.responseText;
        //         console.log('Result: ' + result);
        //         // postResult(result);
        //         }
        //     };
        //     xhr.send(formData);
        // }


       

    </script>

</body>
</html>