# Current Affairs Quiz Application - IONOS Deployment Guide

## Overview
This application provides a dynamic current affairs quiz for journalists and journalism students, adapted for IONOS Web Hosting Expert (PHP-based).

## Prerequisites
- IONOS Web Hosting Expert account
- Google Gemini API key (free tier available)
- FTP access or file manager access

## Step-by-Step Setup

### 1. Get Google Gemini API Key
1. Visit https://makersuite.google.com/app/apikey
2. Sign in with Google account
3. Click "Create API Key"
4. Copy the API key (keep secure)

### 2. Prepare Configuration Files
1. Rename `env-template.txt` to `.env`
2. Edit `.env` and replace `your_actual_api_key_here` with your real API key
3. Rename `htaccess-template.txt` to `.htaccess`

### 3. Upload Files via FTP
Upload these files to your website root:
- `index.html` (main application)
- `quiz-api.php` (backend API)  
- `.env` (environment config)
- `.htaccess` (security config)

### 4. Set File Permissions
Via FTP client or SSH:
- index.html: 644
- quiz-api.php: 644
- .env: 600 (important for security)
- .htaccess: 644

### 5. Test Installation
1. Visit your website URL
2. Should see loading screen then quiz
3. Verify 20 questions load correctly
4. Test submitting answers and viewing results

## Features
- **Dynamic Generation**: Fresh questions twice daily using AI
- **Caching**: Efficient AM/PM caching system
- **Categories**: UK National News, International News, Sports, North West England
- **Responsive**: Works on desktop and mobile
- **Professional**: Designed for journalism education

## Troubleshooting
- **API Error**: Check .env file exists with valid API key
- **Blank Page**: Verify PHP 7.4+ enabled in IONOS control panel
- **Cache Issues**: Ensure web server can write to cache/ directory
- **Loading Issues**: Check browser console for JavaScript errors

## Maintenance
- Monitor API usage in Google Cloud Console
- Review generated questions periodically for quality
- Update quiz categories as needed
- Check cache refresh is working (twice daily)

## Cost
- Google Gemini: Free tier covers typical usage (1500 requests/day)
- Your app: ~40 requests/day (well within free limits)
- IONOS: Standard hosting fee only

## Security
- API keys protected via .htaccess
- Cache directory secured from web access
- Environment variables isolated from public access