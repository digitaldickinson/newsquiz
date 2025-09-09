# Current Affairs Quiz - IONOS Deployment

## Quick Setup (15 minutes)

1. **Get Google Gemini API Key**
   - Visit: https://makersuite.google.com/app/apikey
   - Create free API key

2. **Prepare Files**
   - Rename `env-template.txt` to `.env`
   - Add your API key to `.env` file
   - Rename `htaccess-template.txt` to `.htaccess`

3. **Upload to IONOS**
   - Upload all files to your website root directory
   - Set file permissions: .env (600), others (644)

4. **Test**
   - Visit your website URL
   - Quiz should load automatically

## Files Included:
- `index.html` - Main quiz application
- `quiz-api.php` - PHP backend 
- `env-template.txt` - Environment config (rename to .env)
- `htaccess-template.txt` - Apache config (rename to .htaccess)
- `deployment-guide.md` - Detailed setup instructions
- `setup-checklist.md` - Quick reference checklist

## Support:
- Check deployment-guide.md for detailed instructions
- Ensure PHP 7.4+ enabled in IONOS control panel
- Verify API key has Gemini API access enabled

## Features:
✅ Twice-daily quiz generation (AM/PM)
✅ 20 current affairs questions
✅ Mobile-responsive design  
✅ Color-coded results with explanations
✅ Professional styling for journalism use