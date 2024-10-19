using System.Text;
using Microsoft.AspNetCore.Mvc;
using Moodler.Helpers;

namespace Moodler.Controllers;

public class EncryptController(EncryptHelper encryptHelper) : BaseController
{
    [HttpPost]
    public IActionResult Encrypt(string clearText)
    {
        var encryptedBytes = encryptHelper.Encrypt(clearText);
        
        ViewBag.Key = Convert.ToBase64String(encryptedBytes);
        return View("Index");
    }
}