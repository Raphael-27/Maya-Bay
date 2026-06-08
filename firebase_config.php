<?php

define('FIREBASE_API_KEY', 'AIzaSyBfYfOdPLa5vYFlvZaybtK72yuWlSDQqQM');
define('FIREBASE_PROJECT_ID', 'hotel-maya-bay-8c7db');
define('FIREBASE_AUTH_DOMAIN', 'hotel-maya-bay-8c7db.firebaseapp.com');

define('FIREBASE_DATABASE_URL', '');

// URLs base para la API REST de Firestore y Auth
define(
    'FIRESTORE_BASE_URL',
    'https://firestore.googleapis.com/v1/projects/' .
    FIREBASE_PROJECT_ID .
    '/databases/(default)/documents'
);

define(
    'FIREBASE_AUTH_URL',
    'https://identitytoolkit.googleapis.com/v1/accounts'
);