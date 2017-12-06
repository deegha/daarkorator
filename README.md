# daarkorator

## Project setup
	- Clone the repository 
	- Run database script

### API Endpoints

#### login

- `/login`
- method : post
- request 
	`{
	"email": "shuboothi@gmail.com",
	"password" : "098f6bcd4621d373cade4e832627b4f6"
	}`

- response with no errors
	`{"error":false,"accessToken":"9f27c285e5cddd59abb8970102f25da6","username":"deegha","message":"Successfully authenticated"}`


#### userFeatures

- `/userFeatures`
- method : get
- headers : Authorization
- resquest : 
- response with no errors

`{
    "error": false,
    {"error":false,"features":{"create_user":true,"update_user":true,"delete_user":true,"list_users":true,"create_project":true,"update_project":true,"delete_project":true,"list_projects":true,"update_price":true}}
    }
}`

#### create user

- `/user`  
- method  : post 
- headers : Authorization
- Note : `"save_project" : true ` for save project
- request 
`{
  "first_name": "Shirvin",
  "last_name": "Janze",
  "email": "shirvinjanze@gmail.com",
  "user_type": 3,
  "contact_number": "0711129349",
  "daarkorator_details": {
    "company_name": "testcompany",
    "about": "test about",
    "tranings": "test trainings",
    "tools": "test tools",
    "instagrame": "test insta",
    "website": "test web"
  }
}`



- response with no errors
`{"error":false,"user_id":14}`

#### List users

- `/user`
- method  : get 
- headers : Authorization
- response with no errors
`{
    "error": false,
    "users": [
        {
            "id": "3",
            "user_type": "1",
            "first_name": "jone",
            "last_name": "smith",
            "email": "vass@gmail.com",
            "user_image": "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
            "contact_number": "0322222623",
            "status": "1",
            "company_name": "jadopado",
            "about": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s",
            "tranings": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
            "tools": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
            "instagrame": "https://www.instagram.com/?hl=en",
            "website": "test.com",
            "type_id": "1",
            "type_name": "Admin",
            "status_title": "Active"
        },
        {
            "id": "4",
            "user_type": "1",
            "first_name": "Shirvin",
            "last_name": "Janze",
            "email": "shirvinjanzae@gmail.com",
            "user_image": "",
            "contact_number": "0711129349",
            "status": "1",
            "company_name": "testcompany",
            "about": "test about",
            "tranings": "test trainings",
            "tools": "test tools",
            "instagrame": "test insta",
            "website": "test web",
            "type_id": "1",
            "type_name": "Admin",
            "status_title": "Active"
        },
        {
            "id": "1",
            "user_type": "1",
            "first_name": "deegha",
            "last_name": "Galkissa",
            "email": "shuboothi@gmail.com",
            "user_image": "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
            "contact_number": "0322222623",
            "status": "1",
            "company_name": "testcompany",
            "about": "test about",
            "tranings": "test trainings",
            "tools": "test tools",
            "instagrame": "test insta",
            "website": "test web",
            "type_id": "1",
            "type_name": "Admin",
            "status_title": "Active"
        },
        {
            "id": "2",
            "user_type": "1",
            "first_name": "Chaminda",
            "last_name": "Vass",
            "email": "vass@gmail.com",
            "user_image": "",
            "contact_number": "0322222623",
            "status": "2",
            "company_name": null,
            "about": null,
            "tranings": null,
            "tools": null,
            "instagrame": null,
            "website": null,
            "type_id": "1",
            "type_name": "Admin",
            "status_title": "Inactive"
        }
    ]
}`

#### Delete user

- `/user/:user_id`
- method  : delete 
- headers : Authorization
- response with no errors
`{
    "error": false,
    "message": "User Successfully Deleted"
}`


#### Update user

- `/user/:user_id`
- method  : put 
- headers : Authorization
- response with no errors
`{"error":false,"message":"User updated Successfully"}`
- request body 

`
{
"first_name" :  "deegha",
"last_name" :"Galkissa",
"update_password":true,
"password" : "098f6bcd4621d373cade4e832627b4f6",
"user_image" : "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
"contact_number" : "0322222623",
"daarkorator_details" : {
    "company_name" : "jadopado",
    "about" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s",
    "tranings" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
    "tools" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
    "instagrame":"https://www.instagram.com/?hl=en",
    "website": "test.com"
}
}`

** send request body without `daarkorator_details` attribute for non daarkorator users


#### forgot password

- `/forgotPassword`
- method  : post 
- headers : non
- request :`{"email":"shuboothi@gmail.com"}`
- response with no errors
`{"error":false,"message":"Email sent Successfully"}`


#### Update package

- `/package`
- method  : put
- headers : Authorization
- request : {
            	"price":"23"
            }
- response with no errors
`{"error":false,"message":"Package updated successfully"}`

#### User Signup

-userSignUp
-method : post
-headers: non
-request: 
`{
"first_name" :  "jone",
"last_name" :"smith",
"email": "shuboothi4353ds@gmail.com",
"password" : "098f6bcd4621d373cade4e832627b4f6",
"confirm_password" : "098f6bcd4621d373cade4e832627b4f6",
"user_image" : "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
"contact_number" : "0322222623"
}`
- response with no errors
`{"error": false,"message": "User created successfully"}`


#### get Room types

- `/rooms`
- method : get
- headers : none
- resquest :
- response with no errors
{"error":false,"rooms":[{"id":"1","title":"Living room"},{"id":"2","title":"Dining Room"},{"id":"3","title":"Office"},{"id":"4","title":"Master BedRoom"},{"id":"5","title":"Kid's Bedroom"},{"id":"6","title":"Guest BedRoom"},{"id":"7","title":"Nursery"},{"id":"8","title":"Balcony"}]}

#### get Room images

- `/room-images`
- method : get
- headers : none
- resquest :
- response with no errors
{"error":false,"roomImages":[{"id":"1","image_url":"http:\/\/cdn.home-designing.com\/wp-content\/uploads\/2009\/07\/living-room-arrangement.jpg"},{"id":"2","image_url":"http:\/\/www.amazadesign.com\/wp-content\/uploads\/Admirable-White-Living-Room-Design-Ideas-with-Red-Curtains-Furnished-with-Sofa-and-Chairs-Completed-with-Glass-Round-Table-and-Pendant-Lamp-plus-Wall-Flatscreen-TV.jpg"},{"id":"3","image_url":"https:\/\/s-media-cache-ak0.pinimg.com\/originals\/cb\/90\/80\/cb90809a736cf358c8fc804ad6e78b54.jpg"},{"id":"4","image_url":"http:\/\/cdn.home-designing.com\/wp-content\/uploads\/2009\/07\/living-room-arrangement.jpg"}]}

#### get color choices

- `/color-choices`
- method : get
- headers : none
- resquest :
- response with no errors
{"error":false,"roomColors":[{"id":"5","name":"Blues","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-6-main.png?v=20110818210849"},{"id":"6","name":"Reds","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-7-main.png?v=20110818210849"},{"id":"7","name":"Yellows","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-3-main.png?v=20110818210849"},{"id":"8","name":"Purples","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-2-main.png?v=20110818210849"},{"id":"9","name":"Greens","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-8-main.png?v=20110818210849"}]}


#### get user roles

- `/user/types`
- method : get
- headers : Authorization
- resquest :
- response with no errors
{"error":false,"user_types":[{"id":"1","display_name":"Administrator"},{"id":"2","display_name":"Customer"},{"id":"3","display_name":"Daakor"}]}


#### List single user

- `/user`
- method  : get 
- headers : Authorization
- response with no errors
`{  
   "error":false,
   "users":[  
      {  
         "id":"1",
         "first_name":"deegha",
         "last_name":"Galkissa",
         "email":"shuboothi@gmail.com",
         "user_image":"https:\/\/www.atomix.com.au\/media\/2015\/06\/atomix_user31.png",
         "contact_number":"0322222623",
         "status":"1",
         "company_name":null,
         "about":null,
         "tranings":null,
         "tools":null,
         "instagrame":null,
         "website":null
      }
   ]
}`


#### User resetPasword
 * url - /resetpassword/e7a816f506f942dacb756c882af80fab
 * method - POST
 * params - `{
  "password": "098f6bcd4621d373cade4e832627b4f6",
  "confirmPassword": "098f6bcd4621d373cade4e832627b4f6"
}`
 response with no errors
 {
    {"error":false,"message":"Password updated Successfully"}
 }
 */


 #### Create project

- `/project`
- method  : post 
- headers : Authorization
- Request
`   {  
       "room":{  
          "id":6,
          "displayName":"Guest Bed Room"
       },
       "designStyle":[  
          {  
             "id":2,
             "imgUrl":"http://cdn.home-designing.com/wp-content/uploads/2009/07/living-room-arrangement.jpg",
             "selected":true
          }
       ],
       "colorChoice":{  
          "likeColors":[  
             {  
                "id":3,
                "name":"Yellows",
                "imgUrl":"http://s3.amazonaws.com/colorcombos-images/users/1/color-schemes/color-scheme-3-main.png?v=20110818210849",
                "selected":true
             }
          ],
          "dislikeColors":"Black Red Yellow"
       },
       "roomDetails":{  
          "projectName":"Test Project name",
          "length":10,
          "width":20,
          "height":30,
          "unit":"m/cm",
          "roomImage":null,
          "budget":"900",
          "furnitureImage":null
       },
       "inspirations":{  
          "urls":[  
             {  
                "id":"ac5635bd-339d-43f5-d18e-43b067909c39",
                "url":"http://localhost:4200/project-wizard"
             },
             {  
                "id":"8a00f503-1ac4-cae0-6018-2375cdc56a1d",
                "url":"https://github.com/deegha/daarkorator/tree/develop"
             },
             {  
                "id":"3621e7f9-98b6-1584-542a-5a7286890d81",
                "url":"http://localhost:3333/#"
             }
          ],
          "description":"down vote\naccepted\nI found the solution. Thanks to the comments on the API site: http://www.asp.net/web-api/overview/security/individual-accounts-in-web-api\n\nI had to set the correct header for application/x-www-form-urlencoded; charset=UTF-8 and serialize the object i posted. I canŽt find an Angular serializer method, so I made my own(copy from another stackoverflow site) in JavaScript"
       } 
    }`

    - respose with no errors
    `{
    "error": false,
    "message": "Project successfully created."
}`

#### Activte User

- `/activateUser/:activationKey`
- Method  : put 
- Request : non
- Respose with no errors
    `{
      "error": false,
      "message": "Account was successfully activated"
    }`


#### myProfile

- `/myProfile`
- Method  : get 
- Respose with no errors
`{  
   "error":false,
   "users":[  
      {  
         "id":"1",
         "first_name":"deegha",
         "last_name":"Galkissa",
         "email":"shuboothi@gmail.com",
         "user_image":"https:\/\/www.atomix.com.au\/media\/2015\/06\/atomix_user31.png",
         "contact_number":"0322222623",
         "status":"1",
         "company_name":null,
         "about":null,
         "tranings":null,
         "tools":null,
         "instagrame":null,
         "website":null
      }
   ]
}`

#### Get package
Package

- URL     : /package/:id`
- Method  : get 
- Response with no errors
`{"error":false,"message":"Request successful","price":"23"}`

#### Payment

- URL     : `/payment`
- Method  : POST 
-Request  : 
`{
	"project_id" : 1,
	"amount"	: 2000,
	"first_name"	: "name",
	"last_name"	: "lname",
	"phone"	: "0711129349",
	"street_address"	: "address",
	"address_line_2"	: "address",
	"city"	: "city",
	"state_province_region"	: "region",
	"zip_code"	: "6100",
	"country"	: "srilanka",
	"payment_method"	: "1"
}`
- Respose with no errors
{"error":false,"message":"Payment successful"}

#### Update My profile
- URL     : `/myprofile`
- Method  : GET
- Request : `{  
             "first_name":"Deegha",
             "last_name":"Galkissa",
             "update_password":true,
             "password":"password",
             "user_image":"https://www.atomix.com.au/media/2015/06/atomix_user31.png",
             "contact_number":"0322222623"
             "daarkorator_details" : {
    "company_name" : "jadopado",
    "about" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s",
    "tranings" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
    "tools" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
    "instagrame":"https://www.instagram.com/?hl=en",
    "website": "test.com"
}
          }`
- Response with no errors
`{
    "error": false,
    "message": "User updated Successfully"
}`

#### Image Upload

 URL     : `/fileUplaod`
- Method  : POST


#### Project list

- `/project(/:limit(/:bidding(/:status)))`
- Method  : get
- headers : Authorization
- Response with no errors
- :limit (0,30)(31,30)
- :bidding (yes/ no) yes for all projects open for submission, no for projects belongs to user or admin
- :status 1 - paid and open for bidding, 2 - saved as draft, etc...
{
   "error":false,
   "projects": [
           {
               "id": "26",
               "customer_id": "67",
               "published_date": "2017-08-18 13:33:38",
               "status": "2"
           },
           {
               "id": "28",
               "customer_id": "67",
               "published_date": "2017-08-19 11:15:05",
               "status": "2"
           },
           {
           .......
           }
       ]
}


#### notification count

- `/notificationcount`
- Method  : get
- headers : Authorization
- Response with no errors
{
    "error": false,
    "count": 3
}

#### notification list

- `/notifications(/:limit(/:status))`
- Method  : get
- headers : Authorization
- Response with no errors
{
    "error": false,
    "notifications": [
        {
            "id": "171",
            "notification_text": "d;fl,s;dlf ,;sdlf;l",
            "url": "s;/d,.f;sl,df;l,s;ldf",
            "notification_type": "3"
        },
        {
            "id": "172",
            "notification_text": ";lsd,f;ls,d;lf,;sld,f ;lsfjkndjfkgnjdkf",
            "url": "lkmslfkmskmdfklsmldf",
            "notification_type": "3"
        },
        {
            ......
        }
    ]
}



#### Project details

- `/projectdetails/:project_id`
- Method  : get
- headers : Authorization
- Response with no errors
{
    "error": false,
    "Project_Detail": {
        "title": "Test Project by dhammika 1",
        "about": {
            "room_types": "4",
            "room_type_name": "Master Bedroom",
            "design_styles": [
                {
                    "image_url": "http://www.daakor.com/dev/wp-content/uploads/2016/03/stock-photo-3084330-living-room.jpg",
                    "title": "design 1"
                },
                {
                    "image_url": "http://www.daakor.com/dev/wp-content/uploads/2016/03/stock-photo-34687716-modern-living-room.jpg",
                    "title": "design 2"
                },
                {
                    "image_url": "http://www.daakor.com/dev/wp-content/uploads/2016/03/stock-photo-60408058-scandinavian-living-room.jpg",
                    "title": "design 4"
                },
                {
                    "image_url": "http://www.daakor.com/dev/wp-content/uploads/2016/03/stock-photo-64224157-luxurious-living-room-with-fireplace.jpg",
                    "title": "design 3"
                }
            ],
            "color_palettes": [
                {
                    "image_url": "http://www.daakor.com/dev/wp-content/uploads/2016/03/Reds.png",
                    "title": "Reds"
                },
                {
                    "image_url": "http://www.daakor.com/dev/wp-content/uploads/2016/03/Yellows.png",
                    "title": "Yellows"
                },
                {
                    "image_url": "http://www.daakor.com/dev/wp-content/uploads/2016/03/Oranges.png",
                    "title": "Oranges"
                }
            ],
            "color_excemption": "blue, green"
        },
        "details": {
            "dimensions": {
                "length": "12",
                "width": "34",
                "height": "21",
                "unit": "m/cm"
            },
            "room_images": [
                ""
            ],
            "budget": "23445",
            "furniture_images": [
                ""
            ]
        },
        "inspire": {
            "social_media_links": [
                "https://sites.google.com/zone24x7.com/scientia24x7/home?authuser=1",
                "https://sites.google.com/zone24x7.com/scientia24x7/home?authuser=1"
            ],
            "description": "test description to test the file uploader work"
        },
        "style_boards": null
    }
}


- `/message`
- Method  : POST
- headers : Authorization
- params  : {
            	"project_id": 97,
            	"reciever_email":"dhammikag@zone24x7.com",
            	"message_subject":"Want to change the wall colors",
            	"message_text": "test msg",
            	"reference":10  --------- If reply only - previous msg id needs to be passed
            }
- Response with no errors
{
    "error": false,
    "message": "Message sent successfully!"
}

#### Create style boards

- URL     : `/styleboard`
- Method  : POST 
- Request  : 
    - Post Feilds
        project_id
        daarkorator_id
        style_board_name
        note

    - File (PDF)
        style_board

- Respose with no errors
`{"error":false,"message":"Style board successfully attached"}`

#### Get Style boards

- URL : `/styleboard`
- Method : GET
- Sample Response
`{  
   "error":false,
   "styleboards":[  
      {  
         "id":"2",
         "project_id":"1",
         "styleboard":"D011F27C71D201A59620653B6FC86611.jpg",
         "daarkorator_id":"2",
         "note":"",
         "style_board_name":"test",
         "status":"0",
         "added_time":"2017-09-13 00:18:40"
      },
      {  
         "id":"1",
         "project_id":"1",
         "styleboard":"4031059F7F3D130A80B5831F97B9549B.jpg",
         "daarkorator_id":"2",
         "note":"",
         "style_board_name":"test",
         "status":"0",
         "added_time":"2017-09-13 00:18:26"
      }
   ]
}`

#### Get Style board with ID

- URL : `/styleboard/id`
- Method : GET
- Sample Response
`{  
   "error":false,
   "styleboard":{  
      "id":"1",
      "project_id":"1",
      "styleboard":"4031059F7F3D130A80B5831F97B9549B.jpg",
      "daarkorator_id":"2",
      "note":"",
      "style_board_name":"test",
      "status":"0",
      "added_time":"2017-09-13 00:18:26"
   }
}`

#### Daarkorator Signup

- daarkoratorSignUp
- method : post
- headers: non
- request: 
`{
  "first_name": "Shirvin",
  "last_name": "Janze",
  "email": "scss@gmail.com",
  "user_type": 3,
  "contact_number": "0711129349",
  "daarkorator_details": {
    "company_name": "testcompany",
    "about": "test about",
    "tranings": "test trainings",
    "tools": "test tools",
    "instagrame": "test insta",
    "website": "test web"
  }
}`
- response with no errors
`{"error": false,"message": "User created successfully"}`

#### Approve Daarkorator

- `/approveDaarkoratorer/:id`
- method : PUT
- headers: Autherization
- request: non
- response with no errors
`{"error": false,"message": "User approved Successfully"}`

#### Add project to my project 

- `/addToMyProjects`
- method : post
- headers: Autherization
- request : `{
  "project_id": 111
}`
- response wuth no errors
`{
    "error": false,
    "message": "Added to my projects successfully"
}`


#### Cancel project 

- `/projectCancel/:project_id`
- method : put
- headers: Autherization
- request : -
- response wuth no errors
`{
   {"error":false,"message":"Project successfully canceled"}
}`


#### get messages

- url    : /message
- method : GET
- response without errors
`{
    "error": false,
    "messages": [
        {
            "id": "4",
            "project_name": null,
            "sender": "jone smith",
            "email": "shuboothi4353ds@gmail.com",
            "project_id": "109",
            "message_subject": "different styleboard",
            "date_time": "2017-11-03 13:22:50",
            "styleboard_name": "test9nig messages",
            "status": "0"
        },
        {
            "id": "2",
            "project_name": "s",
            "sender": "Shirvin Janze",
            "email": "shirvinjanze@gmail.com",
            "project_id": "110",
            "message_subject": "previous message",
            "date_time": "2017-10-31 21:42:40",
            "styleboard_name": "1",
            "status": "0"
        },
        {
            "id": "3",
            "project_name": "s",
            "sender": "jone smith",
            "email": "shuboothi4353ds@gmail.com",
            "project_id": "110",
            "message_subject": "first message ",
            "date_time": "2017-10-31 21:42:40",
            "styleboard_name": "1",
            "status": "0"
        },
        {
            "id": "1",
            "project_name": "s",
            "sender": "jone smith",
            "email": "shuboothi4353ds@gmail.com",
            "project_id": "110",
            "message_subject": "test subject",
            "date_time": "2017-10-25 13:29:42",
            "styleboard_name": "1",
            "status": "0"
        }
    ]
}`


#### Message detail

- `/messagedetail/14`
- Method  : GET
- headers : Authorization
- Response with no errors
`{
    "error": false,
    "message": [
        {
            "id": "2",
            "project_name": "s",
            "sender": "shirvinjanze@gmail.com",
            "message_subject": "previous message",
            "message_text": "previous message text",
            "date_time": "2017-10-31 21:42:40",
            "styleboard_name": "1",
            "status": "0"
        },
        {
            "id": "3",
            "project_name": "s",
            "sender": "shuboothi4353ds@gmail.com",
            "message_subject": "first message ",
            "message_text": "first mesasage text",
            "date_time": "2017-10-31 21:42:40",
            "styleboard_name": "1",
            "status": "0"
        },
        {
            "id": "1",
            "project_name": "s",
            "sender": "shuboothi4353ds@gmail.com",
            "message_subject": "test subject",
            "message_text": "message text",
            "date_time": "2017-10-25 13:29:42",
            "styleboard_name": "1",
            "status": "0"
        }
    ]
}`



#### Get new projects

- `newProjects` 
- Method : get
- Headers : Authorization
- Response with no errors
`{
    "error": false,
    "projects": [
        {
            "id": "97",
            "customer_id": "60",
            "published_date": "2017-09-01 11:11:20",
            "status": "1",
            "title": "Test Project by dhammika 1",
            "budget": "23445",
            "status_title": "In progress",
            "image": "http://www.daakor.com/dev/wp-content/uploads/2017/09/master-bedroom.jpg"
        },
        {
            "id": "99",
            "customer_id": "61",
            "published_date": "2017-09-01 15:13:23",
            "status": "1",
            "title": "qwqweqwe",
            "budget": "234234",
            "status_title": "In progress",
            "image": "http://www.daakor.com/dev/wp-content/uploads/2017/09/living-room.jpg"
        },
        {
            "id": "100",
            "customer_id": "60",
            "published_date": "2017-09-07 14:07:44",
            "status": "1",
            "title": "Test Project",
            "budget": "23234",
            "status_title": "In progress",
            "image": "http://www.daakor.com/dev/wp-content/uploads/2017/09/living-room.jpg"
        }
    ]
}`


#### Update notifications

- `/readNotifications/:id` 
- Method : PUT
- Headers : Authorization
- Response with no errors : 
`{"error":false,"message":"Notification updated successfully"}`

### Select styleboard

- `/selectStyleboard`
- Method : PUT
- Body :
`{
	"project_id" : 242,
	"styleboard_id" : 64
}`
- Response with no errors : 
`{"error":false,"message":"Styleboard added to projects successfully"}`