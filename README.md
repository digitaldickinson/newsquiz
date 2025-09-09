# Current Affairs Quiz
This PHP-based application automatically generates a 20-question news quiz twice a day, complete with clickable links to the source articles. It uses Google's Gemini AI to fetch and formulate questions based on the last three days of news, providing a zero-maintenance tool for journalism education.

## Quick Setup (15 minutes)

1. **Get Google Gemini API Key**
   - Visit: https://aistudio.google.com/app/apikey
   - Create free API key

2. **Prepare Files**
   - Rename `env-template.txt` to `.env`
   - Add your API key to `.env` file at the point where it says GEMINI_API_KEY=

3. **Upload to your webserver**
   - Upload all files to your website root directory or the subfolder where you are hosting the quiz
   - If this is in a sub-folder make sure the sub folder permissions are set to 755
   - Set file permissions: .env (600), others (644)

4. **Test**
   - Visit your website URL
   - Quiz should load automatically

## Files Included:
- `index.html` - Main quiz application
- `quiz-api.php` - PHP backend 
- `env-template.txt` - Environment config (rename this to .env on your webserver)
- `prompt.txt` - A text file with a detailed prompt

## Support:
- Check deployment-guide.md for detailed instructions
- Ensure PHP 7.4+ 
- Verify API key has Gemini API access enabled
- Check the prompt and adjust as required - It's currently set to favour Manchester and Greater Manchester in the question selection

## Features:
- Twice-daily quiz generation (AM/PM)
- 20 current affairs questions
- Mobile-responsive design  
- Color-coded results with explanations
- Links to sources.