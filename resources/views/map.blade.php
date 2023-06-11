<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://js.api.here.com/v3/3.1/mapsjs-core.js" type="text/javascript" charset="utf-8"></script>
    <script src="https://js.api.here.com/v3/3.1/mapsjs-service.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" src="https://js.api.here.com/v3/3.1/mapsjs-ui.js"></script>
    <script type="text/javascript" src="https://js.api.here.com/v3/3.1/mapsjs-mapevents.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.7.0.js" integrity="sha256-JlqSTELeR4TLqP0OG9dxM7yDPqX1ox/HfgiSLBj8+kM=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <title>Maps</title>
</head>

<body>
    <div class="d-flex flex-column">
        <div class="my-5 gap-3 mx-3">
            <form>
                <div class="mb-3">
                    <label for="location1" class="form-label">Location 1</label>
                    <div id="input0-container" class="position-relative">
                        <input type="text" class="form-control" id="input0">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="location2" class="form-label">Location 2</label>
                    <div id="input1-container" class="position-relative">
                        <input type="text" class="form-control" id="input1">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Calculate shortest distance</button>
            </form>
        </div>
        <div class="mx-3 mb-3" id="summaryData"></div>
        <div id="panel"></div>
        <div style="width: 95vw; height: 80vh;" id="mapContainer" class="mx-3 mb-3"></div>
    </div>

    <script>
        const apiKey = '{{ env("HERE_API_KEY") }}';
        const platform = new H.service.Platform({
            'apiKey': apiKey
        });
        const defaultMapType = platform.createDefaultLayers();
        const map = new H.Map(
            document.getElementById('mapContainer'),
            defaultMapType.vector.normal.map, {
                zoom: 10,
                center: {
                    lng: 13.4,
                    lat: 52.51
                },
                draggable: true
            },
        );
        const behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(map));
        const ui = H.ui.UI.createDefault(map, defaultMapType);
        const routeInstructionsContainer = document.getElementById('panel');
        let bubble;
        $(() => {
            const coordinates = [];
            /*
            Index cuman boleh 0 atau 1, tujuannya untuk ngetrack click dari user
            */
            let currentIndex = 0;
            const setInteractive = (map) => {
                const provider = map.getBaseLayer().getProvider();
                const style = provider.getStyle();
                const changeListener = () => {
                    if (style.getState() === H.map.Style.State.READY) {
                        style.removeEventListener('change', changeListener);
                        style.setInteractive(['places', 'places.populated-places'], true);
                    }
                };
                style.addEventListener('change', changeListener);
            }
            /*
            Event Listener tap handler ketika map ditap(diklik)
            */
            map.addEventListener('tap', (e) => {
                if (currentIndex == 2) {
                    currentIndex = 0;
                }
                const lastMarker = coordinates[currentIndex];
                const tappedCoordinate = map.screenToGeo(e.currentPointer.viewportX, e.currentPointer.viewportY);
                const latitude = Math.abs(tappedCoordinate.lat.toFixed(4));
                const longitude = Math.abs(tappedCoordinate.lng.toFixed(4));
                const marker = createMarker(latitude, longitude);
                coordinates[currentIndex] = marker;

                if (coordinates[0]) {
                    // tambahkan marker ke map
                    map.addObject(coordinates[0]);
                }

                if (coordinates[1]) {
                    map.addObject(coordinates[1]);
                }
                if (lastMarker) {
                    map.removeObject(lastMarker);
                }
                setLocationName(latitude, longitude, currentIndex);
                currentIndex++;
            });

            const createMarker = (lat, lng) => {
                const marker = new H.map.Marker({
                    lat: lat,
                    lng: lng
                });
                return marker;
            };

            const setLocationName = (lat, lng, i) => {
                try {
                    $.get(
                        `https://revgeocode.search.hereapi.com/v1/revgeocode?at=${lat},${lng}&limit=1&apiKey=${apiKey}`
                    ).done((r) => {
                        const location = r.items[0].address.label;
                        $(`#input${i}`).val(location);
                    }).fail(() => {
                        console.log("fail on reverse geocode");
                    });
                } catch (e) {
                    console.log(e)
                }
            };

            /*
            Event Listener change buat input0
            */
            $("#input0").on('keyup', (e) => {
                const text = e.currentTarget.value;
                console.log(`text 0 = ${text}`);
                $.get(`https://autosuggest.search.hereapi.com/v1/autosuggest?at=52.93175,12.77165&limit=7&lang=en&q=${text}&apiKey=${apiKey}`)
                    .done((r) => {
                        const data = r.items.slice(1, r.items.length - 1).map((v) => {
                            return v.title;
                        });
                        console.log(`data[0] = ${data}`);
                        $("#input0").autocomplete({
                            source: data,
                            appendTo: "#input0-container",
                            position: {
                                my: "left top",
                                at: "left bottom",
                                collision: "none"
                            }
                        });
                    });
            });

            /*
            Event Listener change buat input1
            */
            $("#input1").on('keyup', (e) => {
                const text = e.currentTarget.value;
                console.log(`text 1 = ${text}`);
                $.get(`https://autosuggest.search.hereapi.com/v1/autosuggest?at=52.93175,12.77165&limit=7&lang=en&q=${text}&apiKey=${apiKey}`)
                    .done((r) => {
                        const data = r.items.slice(1, r.items.length - 1).map((v) => {
                            return v.title;
                        });
                        $("#input1").autocomplete({
                            source: data,
                            appendTo: "#input1-container",
                            position: {
                                my: "left top",
                                at: "left bottom",
                                collision: "none"
                            }
                        });
                    });
            });

            $("#input0").on("blur", (e) => {
                const text = e.currentTarget.value;
                console.log(`text 0 blur = ${text}`);
                $.get(`https://discover.search.hereapi.com/v1/discover?at=52.93175,12.77165&limit=2&q=${text}&apiKey=${apiKey}`)
                    .done((r) => {
                        const location = r.items[0].position;
                        const lat = location.lat;
                        const lng = location.lng;
                        console.log(`lat = ${lat}, lng = ${lng} blur 0`);
                        const lastMarker = coordinates[currentIndex];
                        if (currentIndex == 2) {
                            currentIndex = 0;
                        }
                        const marker = createMarker(lat, lng);
                        map.addObject(marker);
                        coordinates[currentIndex] = marker;
                        if (lastMarker) {
                            map.removeObject(lastMarker);
                        }
                        currentIndex++;
                    });
            });

            $("#input1").on("blur", (e) => {
                const text = e.currentTarget.value;
                console.log(`text 1 blur = ${text}`);
                $.get(`https://discover.search.hereapi.com/v1/discover?at=52.93175,12.77165&limit=2&q=${text}&apiKey=${apiKey}`)
                    .done((r) => {
                        const location = r.items[0].position;
                        const lat = location.lat;
                        const lng = location.lng;
                        console.log(`lat = ${lat}, lng = ${lng} blur 1`);
                        const lastMarker = coordinates[currentIndex];
                        if (currentIndex == 2) {
                            currentIndex = 0;
                        }
                        const marker = createMarker(lat, lng);
                        map.addObject(marker);
                        coordinates[currentIndex] = marker;
                        if (lastMarker) {
                            map.removeObject(lastMarker);
                        }
                        currentIndex++;
                    });
            });

            setInteractive(map)
        });

        const openBubble = (position, text) => {
            if (!bubble) {
                bubble = new H.ui.InfoBubble(
                    position, {
                        content: text
                    });
                ui.addBubble(bubble);
            } else {
                bubble.setPosition(position);
                bubble.setContent(text);
                bubble.open();
            }
        }

        const addRouteShapeToMap = (route) => {
            route.sections.forEach((section) => {
                const linestring = H.geo.LineString.fromFlexiblePolyline(section.polyline);
                const polyline = new H.map.Polyline(linestring, {
                    style: {
                        lineWidth: 4,
                        strokeColor: 'rgba(0, 128, 255, 0.7)'
                    }
                });
                map.addObject(polyline);
                map.getViewModel().setLookAtData({
                    bounds: polyline.getBoundingBox()
                });
            });
        }

        const addManueversToMap = (route) => {
            let svgMarkup = '<svg width="18" height="18" ' +
                'xmlns="http://www.w3.org/2000/svg">' +
                '<circle cx="8" cy="8" r="8" ' +
                'fill="#1b468d" stroke="white" stroke-width="1" />' +
                '</svg>',
                dotIcon = new H.map.Icon(svgMarkup, {
                    anchor: {
                        x: 8,
                        y: 8
                    }
                }),
                group = new H.map.Group(),
                i,
                j;

            route.sections.forEach((section) => {
                const poly = H.geo.LineString.fromFlexiblePolyline(section.polyline).getLatLngAltArray();

                const actions = section.actions;
                for (i = 0; i < actions.length; i += 1) {
                    const action = actions[i];
                    const marker = new H.map.Marker({
                        lat: poly[action.offset * 3],
                        lng: poly[action.offset * 3 + 1]
                    }, {
                        icon: dotIcon
                    });
                    marker.instruction = action.instruction;
                    group.addObject(marker);
                }

                group.addEventListener('tap', function(evt) {
                    map.setCenter(evt.target.getGeometry());
                    openBubble(evt.target.getGeometry(), evt.target.instruction);
                }, false);
                map.addObject(group);
            });
        }

        const addWaypointsToPanel = (route) => {
            const nodeH3 = document.createElement('h3');
            const labels = [];

            route.sections.forEach((section) => {
                labels.push(
                    section.turnByTurnActions[0].nextRoad.name[0].value)
                labels.push(
                    section.turnByTurnActions[section.turnByTurnActions.length - 1].currentRoad.name[0].value)
            });

            nodeH3.textContent = labels.join(' - ');
            routeInstructionsContainer.innerHTML = '';
            routeInstructionsContainer.appendChild(nodeH3);
        }

        const addSummaryToPanel = (route) => {
            let duration = 0;
            let distance = 0;

            route.sections.forEach((section) => {
                distance += section.travelSummary.length;
                duration += section.travelSummary.duration;
            });

            const summaryDiv = document.createElement('div'),
                content = '<b>Total distance</b>: ' + distance + 'm. <br />' +
                '<b>Travel Time</b>: ' + toMMSS(duration) + ' (in current traffic)';

            summaryDiv.style.fontSize = 'small';
            summaryDiv.style.marginLeft = '5%';
            summaryDiv.style.marginRight = '5%';
            summaryDiv.innerHTML = content;
            routeInstructionsContainer.appendChild(summaryDiv);
        }

        const calculateRoutes = (platform, origin, destination) => {
            const router = platform.getRoutingService(null, 8),
                routeRequestParams = {
                    routingMode: 'fast',
                    transportMode: 'car',
                    origin: origin,
                    destination: destination,
                    return: 'polyline,turnByTurnActions,actions,instructions,travelSummary'
                };

            router.calculateRoute(
                routeRequestParams,
                onSuccess,
                onError
            );
        };

        const onSuccess = (result) => {
            const route = result.routes[0];
            addRouteShapeToMap(route);
            addManueversToMap(route);
            addWaypointsToPanel(route);
            addManueversToPanel(route);
            addSummaryToPanel(route);
        }

        const onError = (e) => {
            console.log(console.log(`error calculating shortest distance = ${e}`));
        }

        const addManueversToPanel = (route) => {
            var nodeOL = document.createElement('ol');

            nodeOL.style.fontSize = 'small';
            nodeOL.style.marginLeft = '5%';
            nodeOL.style.marginRight = '5%';
            nodeOL.className = 'directions';

            route.sections.forEach((section) => {
                section.actions.forEach((action, idx) => {
                    const li = document.createElement('li');
                    const spanArrow = document.createElement('span');
                    const spanInstruction = document.createElement('span');
                    spanArrow.className = 'arrow ' + (action.direction || '') + action.action;
                    spanInstruction.innerHTML = section.actions[idx].instruction;
                    li.appendChild(spanArrow);
                    li.appendChild(spanInstruction);

                    nodeOL.appendChild(li);
                });
            });
            routeInstructionsContainer.appendChild(nodeOL);
        }


        const toMMSS = (duration) => {
            return Math.floor(duration / 60) + ' minutes ' + (duration % 60) + ' seconds.';
        }

        $('form').submit(async (e) => {
            e.preventDefault();
            const location1 = $("#input0").val();
            const location2 = $("#input1").val();
            const res1 = await fetch(`https://discover.search.hereapi.com/v1/discover?at=52.93175,12.77165&limit=2&q=${location1}&apiKey=${apiKey}`, {
                method: "GET",
            });
            const res2 = await fetch(`https://discover.search.hereapi.com/v1/discover?at=52.93175,12.77165&limit=2&q=${location2}&apiKey=${apiKey}`, {
                method: "GET",
            });
            const jsonRes1 = await res1.json();
            const locationRes1 = jsonRes1.items[0].position;
            const lat1 = locationRes1.lat;
            const lng1 = locationRes1.lng;
            const jsonRes2 = await res2.json();
            const locationRes2 = jsonRes2.items[0].position;
            const lat2 = locationRes2.lat;
            const lng2 = locationRes2.lng;
            console.log(`lat1 lng1 = ${lat1},${lng1}`);
            console.log(`lat2 lng2 = ${lat2},${lng2}`);
            await calculateRoutes(platform, `${lat1},${lng1}`, `${lat2},${lng2}`);
        })
    </script>
</body>

</html>