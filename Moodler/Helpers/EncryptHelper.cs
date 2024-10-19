using System.Security.Cryptography;
using System.Text;

namespace Moodler.Helpers;

public class EncryptHelper
{
    // Create byte array for additional entropy when using Protect method.
    static byte[] s_additionalEntropy = { 9, 8, 7, 6, 5 };
    
    public byte[] Encrypt(string clearText)
    {
        byte[] clearBytes = Encoding.UTF8.GetBytes(clearText);
        byte[] encryptedBytes =
            ProtectedData.Protect(clearBytes, s_additionalEntropy, DataProtectionScope.LocalMachine);
        return encryptedBytes;
    }

    public string Decrypt(string cipherText)
    {
        try
        {
            byte[] cipherBytes = Convert.FromBase64String(cipherText);
            byte[] decryptedBytes =
                ProtectedData.Unprotect(cipherBytes, s_additionalEntropy, DataProtectionScope.LocalMachine);
            return Encoding.UTF8.GetString(decryptedBytes);
        }
        catch (Exception e)
        {
            throw new ApplicationException("Failed to decrypt data", e);
        }
    }
}